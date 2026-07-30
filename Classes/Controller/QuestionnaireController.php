<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Controller;

use ProudNerds\PnQuestionnaire\Domain\Model\AdviceBlock;
use ProudNerds\PnQuestionnaire\Domain\Model\Question;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;
use ProudNerds\PnQuestionnaire\Domain\Repository\QuestionnaireRepository;
use ProudNerds\PnQuestionnaire\Exception\UnconfiguredRecordTableException;
use ProudNerds\PnQuestionnaire\Service\ConditionEvaluatorService;
use ProudNerds\PnQuestionnaire\Service\DomainRecordResolverService;
use ProudNerds\PnQuestionnaire\Service\MailRateLimitService;
use ProudNerds\PnQuestionnaire\Service\ProgressService;
use ProudNerds\PnQuestionnaire\Service\ResultMailService;
use ProudNerds\PnQuestionnaire\Service\ResultResolverService;
use ProudNerds\PnQuestionnaire\Service\ResultStorageService;
use ProudNerds\PnQuestionnaire\Service\ScoringService;
use ProudNerds\PnQuestionnaire\Service\SessionService;
use ProudNerds\PnQuestionnaire\Service\StatisticsService;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Extbase\Annotation\Validate;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * QuestionnaireController
 *
 * Handles all frontend actions for the questionnaire plugin.
 *
 * Action flow
 * ──────────────────────────────────────────────────────────────────
 *  introAction       → shows the intro screen (or skips to questionAction)
 *  questionAction    → shows one question; determines progress/nav context
 *  processAction     → stores the submitted answer; advances or terminates
 *  resultAction      → resolves the matching result page; renders or redirects
 *  savedResultAction → restores a run stored earlier and hands over to resultAction
 *  mailResultAction  → mails the outcome plus the saved link to a typed-in address
 * ──────────────────────────────────────────────────────────────────
 *
 * Session answers are stored and retrieved via SessionService.
 * Visible questions are determined per request by ConditionEvaluatorService.
 * All actions are registered as uncached in ext_localconf.php.
 */
class QuestionnaireController extends ActionController
{
    public function __construct(
        private readonly QuestionnaireRepository $questionnaireRepository,
        private readonly SessionService $sessionService,
        private readonly ConditionEvaluatorService $conditionEvaluatorService,
        private readonly ProgressService $progressService,
        private readonly ScoringService $scoringService,
        private readonly ResultResolverService $resultResolverService,
        private readonly DomainRecordResolverService $domainRecordResolverService,
        private readonly ResultStorageService $resultStorageService,
        private readonly ResultMailService $resultMailService,
        private readonly MailRateLimitService $mailRateLimitService,
        private readonly StatisticsService $statisticsService,
        private readonly LoggerInterface $logger,
    ) {}

    // ─────────────────────────────────────────────────────────────────────────
    // Actions
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Show the optional introduction screen before the first question.
     *
     * When the FlexForm `introduction_screen` toggle is off, immediately
     * redirect to the first question so the intro is never shown.
     *
     * @return ResponseInterface
     */
    public function introAction(): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->htmlResponse();
        }

        // Skip intro if disabled by the editor in FlexForm
        if (!$this->isIntroScreenEnabled()) {
            return $this->redirect('question');
        }

        $this->view->assign('questionnaire', $questionnaire);

        return $this->htmlResponse();
    }

    /**
     * Show one question step.
     *
     * When `$questionUid` is 0 (default, used from the intro "Start" button),
     * the first visible question is shown automatically.
     *
     * When a question UID is passed but it is no longer in the visible list
     * (e.g., a condition changed after a back-navigation), the controller
     * falls back to the first visible question.
     *
     * @param int $questionUid UID of the question to show; 0 = first visible question
     * @return ResponseInterface
     */
    public function questionAction(int $questionUid = 0): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->htmlResponse();
        }

        $feUser = $this->getFeUser();
        if ($feUser === null) {
            return $this->htmlResponse();
        }

        $questionnaireUid = (int)$questionnaire->getUid();
        $this->countUsageOnce($feUser, $questionnaireUid, SessionService::COUNTED_START);

        $sessionAnswers   = $this->sessionService->getAnswers($feUser, $questionnaireUid);
        $visibleQuestions = $this->conditionEvaluatorService->getVisibleQuestions(
            $questionnaire->getQuestions(),
            $sessionAnswers
        );

        if ($visibleQuestions === []) {
            // All questions are conditional and none are visible — go straight to result
            return $this->redirect('result');
        }

        // Resolve which question to display
        if ($questionUid === 0) {
            $question = reset($visibleQuestions);
        } else {
            $question = $this->findQuestionByUid($visibleQuestions, $questionUid)
                ?? reset($visibleQuestions);
        }

        $currentUid      = (int)$question->getUid();
        $progress        = $this->progressService->calculate($visibleQuestions, $currentUid);
        $prevQuestionUid = $this->progressService->getPreviousQuestionUid($visibleQuestions, $currentUid);
        $currentAnswer   = $this->sessionService->getAnswer($feUser, $questionnaireUid, $currentUid);

        $progressPercentage = $progress['total'] > 0
            ? (int)round($progress['current'] / $progress['total'] * 100)
            : 0;

        // Decides between "Next" and "Finish" on the submit button. Provisional by
        // nature: answering this question can reveal a conditional one, after which
        // there is another step. `>=` rather than `==` so an unexpected overshoot
        // still reads as the closing step instead of falling back to "Next".
        $isLastQuestion = $progress['total'] > 0 && $progress['current'] >= $progress['total'];

        $answerTypePartialMap = [
            Question::TYPE_SINGLE_CHOICE   => 'AnswerTypes/SingleChoice',
            Question::TYPE_MULTIPLE_CHOICE => 'AnswerTypes/MultipleChoice',
            Question::TYPE_YES_NO          => 'AnswerTypes/YesNo',
            Question::TYPE_SCALE           => 'AnswerTypes/Scale',
            Question::TYPE_INFORMATIONAL   => 'AnswerTypes/Informational',
        ];

        $this->view->assignMultiple([
            'questionnaire'      => $questionnaire,
            'question'           => $question,
            'progress'           => $progress,
            'progressPercentage' => $progressPercentage,
            'isLastQuestion'     => $isLastQuestion,
            'prevQuestionUid'    => $prevQuestionUid,
            'currentAnswer'      => $currentAnswer,
            'hasAnswers'         => $sessionAnswers !== [],
            'scaleDisplay'      => $question->getScaleDisplay(),
            'answerTypePartial' => $answerTypePartialMap[$question->getType()] ?? 'AnswerTypes/SingleChoice',
            'scaleRange'        => $question->isScale()
                ? range($question->getScaleMin(), $question->getScaleMax())
                : [],
            'scaleMiddle'       => $question->isScale()
                ? (int)round(($question->getScaleMin() + $question->getScaleMax()) / 2)
                : 0,
        ]);

        return $this->htmlResponse();
    }

    /**
     * Process a submitted answer and advance to the next step.
     *
     * Stores the answer in the session, re-evaluates conditions with the
     * updated answers, then either redirects to the next visible question
     * or to `resultAction` when the last question has been answered.
     *
     * Server-side required-field validation is applied as a guard against
     * submissions that bypass browser-level validation.
     *
     * @param int          $questionUid UID of the question that was answered
     * @param array<mixed> $answers     Selected answer option UIDs (or scale value)
     * @return ResponseInterface
     */
    public function processAction(int $questionUid, array $answers = []): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->redirect('intro');
        }

        $feUser = $this->getFeUser();
        if ($feUser === null) {
            return $this->redirect('intro');
        }

        $questionnaireUid = (int)$questionnaire->getUid();

        // Locate the current question to check required constraint
        $sessionAnswers   = $this->sessionService->getAnswers($feUser, $questionnaireUid);
        $visibleQuestions = $this->conditionEvaluatorService->getVisibleQuestions(
            $questionnaire->getQuestions(),
            $sessionAnswers
        );
        $currentQuestion = $this->findQuestionByUid($visibleQuestions, $questionUid);

        // Server-side required validation (guard against JS bypass)
        if ($currentQuestion !== null
            && $currentQuestion->isRequired()
            && !$currentQuestion->isInformational()
            && $answers === []
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('error.required', 'PnQuestionnaire') ?? 'This field is required.',
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('question', null, null, ['questionUid' => $questionUid]);
        }

        // Sanitise and persist the answer
        $sanitisedAnswers = array_values(array_map('strval', $answers));
        $this->sessionService->storeAnswer($feUser, $questionnaireUid, $questionUid, $sanitisedAnswers);

        // Re-evaluate conditions with the freshly updated answers
        $updatedAnswers   = $this->sessionService->getAnswers($feUser, $questionnaireUid);
        $visibleQuestions = $this->conditionEvaluatorService->getVisibleQuestions(
            $questionnaire->getQuestions(),
            $updatedAnswers
        );

        // Advance to next visible question, or terminate at the result
        $nextUid = $this->progressService->getNextQuestionUid($visibleQuestions, $questionUid);

        if ($nextUid !== null) {
            return $this->redirect('question', null, null, ['questionUid' => $nextUid]);
        }

        return $this->redirect('result');
    }

    /**
     * Resolve and display (or redirect to) the matching result page.
     *
     * Evaluation order (first match wins):
     *  1. Score-range trigger
     *  2. Specific-answer trigger
     *  3. Combination trigger (score + answer)
     *  4. Catch-all fallback
     *
     * For inline outcomes the view is rendered in-place with result content
     * and filtered advice blocks. For redirect outcomes the visitor is sent
     * to the configured target (page, URL or domain record detail view).
     *
     * @return ResponseInterface
     */
    public function resultAction(): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->htmlResponse();
        }

        $feUser = $this->getFeUser();
        if ($feUser === null) {
            return $this->htmlResponse();
        }

        $questionnaireUid = (int)$questionnaire->getUid();
        $sessionAnswers   = $this->sessionService->getAnswers($feUser, $questionnaireUid);

        $outcome = $this->resolveOutcome($questionnaire, $sessionAnswers);
        if ($outcome === null) {
            return $this->htmlResponse();
        }

        // Reaching an outcome completes the run — but only for a visitor who answered
        // something: opening a result URL without a session is not a completed run.
        if ($sessionAnswers !== []) {
            $this->countUsageOnce($feUser, $questionnaireUid, SessionService::COUNTED_COMPLETION);
        }

        $resultPage       = $outcome['resultPage'];
        $adviceBlocks     = $outcome['adviceBlocks'];
        $totalScore       = $outcome['totalScore'];
        $visibleQuestions = $outcome['visibleQuestions'];

        // Redirect-type outcomes hand off to an external target
        if ($resultPage->isRedirectOutcome()) {
            return $this->handleRedirectOutcome($resultPage);
        }

        // Pre-build answer summary for template (Fluid cannot do dynamic array key lookups)
        $answerSummary = [];
        if ((bool)($this->settings['show_answer_summary'] ?? false)) {
            foreach ($visibleQuestions as $summaryQuestion) {
                if ($summaryQuestion->isInformational()) {
                    continue;
                }
                $selectedValues = $sessionAnswers[(string)$summaryQuestion->getUid()] ?? [];
                if ($selectedValues === []) {
                    continue;
                }
                $selectedLabels = $summaryQuestion->isScale()
                    ? $selectedValues
                    : array_values(array_filter(
                        array_map(static function ($option) use ($selectedValues) {
                            return in_array((string)$option->getUid(), $selectedValues, true)
                                ? $option->getLabel()
                                : null;
                        }, iterator_to_array($summaryQuestion->getAnswerOptions()))
                    ));

                $answerSummary[] = [
                    'question' => $summaryQuestion->getQuestionText(),
                    'answers'  => $selectedLabels,
                ];
            }
        }

        // Every completed run is stored when the editor enabled it, so the visitor
        // finds the saved link ready on the page instead of having to ask for it.
        $savedResult = null;
        if ((bool)($this->settings['db_save_result_enabled'] ?? false)) {
            $savedResult = $this->resultStorageService->storeForCurrentRun(
                $feUser,
                $questionnaireUid,
                $sessionAnswers,
                $totalScore,
                $this->resolveSavedResultStoragePid(),
                $this->resolveSavedResultLifetimeDays()
            );
            $this->resultStorageService->rememberResultUrl(
                $savedResult,
                $this->buildSavedResultUrl($savedResult->getToken())
            );
        }

        $this->view->assignMultiple([
            'questionnaire'     => $questionnaire,
            'resultPage'        => $resultPage,
            'adviceBlocks'      => $adviceBlocks,
            'totalScore'        => $totalScore,
            'showScore'         => (bool)($this->settings['show_score'] ?? false),
            'showAnswerSummary' => (bool)($this->settings['show_answer_summary'] ?? false),
            'answerSummary'     => $answerSummary,
            'visibleQuestions'  => $visibleQuestions,
            'savedResult'       => $savedResult,
            // Mailing carries the saved link, so the form is pointless without storing
            'mailResultEnabled' => $savedResult !== null
                && (bool)($this->settings['mail_result_enabled'] ?? false),
        ]);

        return $this->htmlResponse();
    }

    /**
     * Show the outcome of a run that was stored earlier, addressed by its token.
     *
     * Works without an active session: the stored answers are restored before
     * handing over to resultAction(). That recalculates the outcome against the
     * questionnaire as it is today, lets the visitor change the answers from
     * there, and — because the token is back in the session — updates the
     * existing row instead of writing a second one.
     */
    public function savedResultAction(string $token = ''): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->htmlResponse();
        }

        $feUser = $this->getFeUser();
        if ($feUser === null) {
            return $this->htmlResponse();
        }

        $questionnaireUid = (int)$questionnaire->getUid();
        $savedResult      = $this->resultStorageService->findValidByToken($token);

        // A token belonging to another questionnaire counts as unknown: restoring
        // it would fill the session of the questionnaire shown here with answers
        // from elsewhere. Malformed, unknown, expired, discarded and mismatched
        // all give the same message, so nothing can be inferred from it.
        if ($savedResult === null || $savedResult->getQuestionnaire() !== $questionnaireUid) {
            $this->addFlashMessage(
                LocalizationUtility::translate('savedResult.notFound', 'PnQuestionnaire')
                    ?? 'This saved result is no longer available.',
                '',
                ContextualFeedbackSeverity::INFO
            );

            return $this->redirect('intro');
        }

        $this->sessionService->restoreRun(
            $feUser,
            $questionnaireUid,
            $savedResult->getGivenAnswers(),
            $savedResult->getToken()
        );

        return $this->redirect('result');
    }

    /**
     * Mail the outcome of the run in progress to an address the visitor types in.
     *
     * The token comes from the session and not from the submitted form: a visitor can
     * therefore only mail their own outcome, and there is no token parameter to tamper
     * with. Sending is followed by a redirect so a refresh cannot send a second time.
     */
    #[Validate(['validator' => 'NotEmpty', 'param' => 'recipient'])]
    #[Validate(['validator' => 'EmailAddress', 'param' => 'recipient'])]
    public function mailResultAction(string $recipient, string $website = ''): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();
        if ($questionnaire === null) {
            return $this->redirect('intro');
        }

        $feUser = $this->getFeUser();
        if ($feUser === null) {
            return $this->redirect('intro');
        }

        if (!$this->isMailResultEnabled()) {
            return $this->redirect('result');
        }

        // The honeypot is hidden from view and from assistive technology, so only an
        // automated submission fills it. Answered with the same confirmation a real
        // send gets: an error message would teach a bot which field to leave alone.
        if (trim($website) !== '') {
            $this->addFlashMessage(
                LocalizationUtility::translate('mail.sent', 'PnQuestionnaire') ?? 'Your result has been sent.'
            );

            return $this->redirect('result');
        }

        // Checked before anything is built, and after validation so a typo costs no
        // allowance. Both counters are consumed here, so this must run once per attempt.
        if (!(bool)($this->settings['mail_rate_limit_disabled'] ?? false)
            && !$this->mailRateLimitService->isWithinLimit($recipient, $this->request)
        ) {
            $this->addFlashMessage(
                LocalizationUtility::translate('mail.rateLimited', 'PnQuestionnaire')
                    ?? 'You have sent this too often. Try again later.',
                '',
                ContextualFeedbackSeverity::ERROR
            );

            return $this->redirect('result');
        }

        $questionnaireUid = (int)$questionnaire->getUid();
        $savedResult      = $this->resultStorageService->findValidByToken(
            $this->sessionService->getResultToken($feUser, $questionnaireUid)
        );

        // No usable run behind the session token: the same message as an unknown token,
        // because the visitor cannot act on the difference either way.
        if ($savedResult === null || $savedResult->getQuestionnaire() !== $questionnaireUid) {
            $this->addFlashMessage(
                LocalizationUtility::translate('savedResult.notFound', 'PnQuestionnaire')
                    ?? 'This saved result is no longer available.',
                '',
                ContextualFeedbackSeverity::INFO
            );

            return $this->redirect('intro');
        }

        $outcome = $this->resolveOutcome(
            $questionnaire,
            $this->sessionService->getAnswers($feUser, $questionnaireUid)
        );

        // A redirect outcome has no text to mail, and the form is never reachable from
        // one — this only guards against the outcome changing between the two requests.
        if ($outcome === null || $outcome['resultPage']->isRedirectOutcome()) {
            return $this->redirect('result');
        }

        $sent = $this->resultMailService->sendSavedResult(
            $recipient,
            $this->request,
            $questionnaire,
            $outcome['resultPage'],
            $outcome['adviceBlocks'],
            $savedResult,
            $this->settings
        );

        $this->addFlashMessage(
            $sent
                ? LocalizationUtility::translate('mail.sent', 'PnQuestionnaire') ?? 'Your result has been sent.'
                : LocalizationUtility::translate('mail.failed', 'PnQuestionnaire') ?? 'Sending failed.',
            '',
            $sent ? ContextualFeedbackSeverity::OK : ContextualFeedbackSeverity::ERROR
        );

        return $this->redirect('result');
    }

    /**
     * Clear all stored answers for this questionnaire and restart from the beginning.
     *
     * @return ResponseInterface
     */
    public function resetAction(): ResponseInterface
    {
        $questionnaire = $this->loadQuestionnaire();

        if ($questionnaire !== null) {
            $feUser = $this->getFeUser();
            if ($feUser !== null) {
                $this->sessionService->clearSession($feUser, (int)$questionnaire->getUid());
            }
        }

        return $this->redirect('intro');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Resolve which outcome the given answers lead to, and which advice blocks go with it.
     *
     * Shared by resultAction() and mailResultAction() so the mail cannot drift from the
     * page it reproduces. Returns null when nothing matched; the caller decides what to
     * show, and a redirect-type outcome is the caller's business too.
     *
     * @param array<int|string, array<string>> $sessionAnswers
     * @return array{resultPage: ResultPage, adviceBlocks: array<AdviceBlock>, totalScore: float, visibleQuestions: array<Question>}|null
     */
    private function resolveOutcome(Questionnaire $questionnaire, array $sessionAnswers): ?array
    {
        $visibleQuestions = $this->conditionEvaluatorService->getVisibleQuestions(
            $questionnaire->getQuestions(),
            $sessionAnswers
        );

        // Score (0.0 when scoring is disabled on the questionnaire)
        $totalScore = $this->scoringService->calculateTotal($visibleQuestions, $sessionAnswers);

        // First matching result page (top-to-bottom, first match wins)
        $resultPage = $this->resultResolverService->resolve($questionnaire, $sessionAnswers, $totalScore);

        if ($resultPage === null) {
            $this->logger->warning(
                'pn_questionnaire: No result page matched — add a catch-all result page.',
                ['questionnaireUid' => $questionnaire->getUid()]
            );

            return null;
        }

        return [
            'resultPage'       => $resultPage,
            'adviceBlocks'     => $this->resultResolverService->filterAdviceBlocks(
                $resultPage,
                $sessionAnswers,
                $totalScore
            ),
            'totalScore'       => $totalScore,
            'visibleQuestions' => $visibleQuestions,
        ];
    }

    /**
     * Whether the visitor may mail their outcome. Mailing carries the saved link, so it
     * takes both toggles: without storing there is no link and nothing to mail.
     */
    private function isMailResultEnabled(): bool
    {
        return (bool)($this->settings['db_save_result_enabled'] ?? false)
            && (bool)($this->settings['mail_result_enabled'] ?? false);
    }

    /**
     * Suppress the generic Extbase message on a validation error: the mail form shows
     * the failure at the field itself, which names what is wrong and where.
     */
    protected function getErrorFlashMessage(): bool
    {
        return false;
    }

    /**
     * Build a response that redirects to the target defined by a redirect-type
     * result page (internal_page, external_url, domain_record).
     */
    private function handleRedirectOutcome(ResultPage $resultPage): ResponseInterface
    {
        try {
            $targetUri = match ($resultPage->getOutcomeType()) {
                ResultPage::OUTCOME_INTERNAL_PAGE => $this->uriBuilder
                    ->reset()
                    ->setTargetPageUid($resultPage->getPageUid())
                    ->setCreateAbsoluteUri(true)
                    ->build(),
                ResultPage::OUTCOME_EXTERNAL_URL  => $resultPage->getExternalUrl(),
                ResultPage::OUTCOME_DOMAIN_RECORD => $this->domainRecordResolverService->resolveUri(
                    $resultPage->getRecordUid(),
                    $this->settings,
                    $this->request
                ),
                default => '',
            };
        } catch (UnconfiguredRecordTableException $e) {
            $this->logger->error(
                'pn_questionnaire: Domain record result could not be resolved — ' . $e->getMessage(),
                ['resultPageUid' => $resultPage->getUid()]
            );

            return $this->htmlResponse();
        }

        if ($targetUri === '') {
            $this->logger->error(
                'pn_questionnaire: Result page has an empty redirect URI.',
                ['resultPageUid' => $resultPage->getUid()]
            );

            return $this->htmlResponse();
        }

        return $this->redirectToUri($targetUri);
    }

    /**
     * Load the questionnaire record referenced in the plugin FlexForm settings.
     *
     * Returns null (and logs an error) when no questionnaire is configured
     * or the record cannot be found.
     */
    private function loadQuestionnaire(): ?Questionnaire
    {
        $questionnaireUid = (int)($this->settings['questionnaire'] ?? 0);

        if ($questionnaireUid === 0) {
            $this->logger->error('pn_questionnaire: No questionnaire UID configured in the plugin FlexForm.');
            return null;
        }

        $questionnaire = $this->questionnaireRepository->findByUidIgnoringStoragePage($questionnaireUid);

        if ($questionnaire === null) {
            $this->logger->error(
                'pn_questionnaire: Questionnaire record not found.',
                ['uid' => $questionnaireUid]
            );
        }

        return $questionnaire;
    }

    /**
     * Find a Question in the visible list by its UID.
     *
     * @param array<Question> $visibleQuestions
     */
    private function findQuestionByUid(array $visibleQuestions, int $questionUid): ?Question
    {
        foreach ($visibleQuestions as $question) {
            if ($question->getUid() === $questionUid) {
                return $question;
            }
        }

        return null;
    }

    /**
     * Retrieve the FrontendUserAuthentication from the current request attribute.
     *
     * Should never return null in a normal TYPO3 frontend context — the
     * FrontendUserAuthentication middleware always runs before controllers.
     */
    private function getFeUser(): ?FrontendUserAuthentication
    {
        $feUser = $this->request->getAttribute('frontend.user');

        if (!$feUser instanceof FrontendUserAuthentication) {
            $this->logger->error('pn_questionnaire: FrontendUserAuthentication not available in request.');
            return null;
        }

        return $feUser;
    }

    /**
     * Return true when the intro screen is enabled for this plugin placement.
     */
    private function isIntroScreenEnabled(): bool
    {
        return (bool)($this->settings['introduction_screen'] ?? true);
    }

    /**
     * Where stored results land, in three steps: the FlexForm field, then the
     * TypoScript default, then the page the plugin sits on. The last step keeps the
     * extension working out of the box instead of silently writing to pid 0, but it
     * is a fallback and therefore logged.
     */
    /**
     * Raise a usage counter for this run, at most once. The flag lives in the session, so
     * a refresh of the page — or a later visit through the saved link — leaves the number
     * alone; only starting over counts as a new run.
     */
    private function countUsageOnce(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        string $counter
    ): void {
        if (!(bool)($this->settings['statistics_enabled'] ?? false)) {
            return;
        }
        if ($this->sessionService->hasCounted($feUser, $questionnaireUid, $counter)) {
            return;
        }

        if ($counter === SessionService::COUNTED_START) {
            $this->statisticsService->countStart($questionnaireUid);
        } else {
            $this->statisticsService->countCompletion($questionnaireUid);
        }

        $this->sessionService->markCounted($feUser, $questionnaireUid, $counter);
    }

    private function resolveSavedResultStoragePid(): int
    {
        $configuredPid = (int)($this->settings['db_save_result_storage_pid'] ?? 0);
        if ($configuredPid > 0) {
            return $configuredPid;
        }

        $routing = $this->request->getAttribute('routing');
        $currentPageUid = $routing instanceof PageArguments ? $routing->getPageId() : 0;

        $this->logger->warning(
            'pn_questionnaire: No storage folder configured for saved results — falling back to the current page.',
            ['pid' => $currentPageUid]
        );

        return $currentPageUid;
    }

    private function resolveSavedResultLifetimeDays(): int
    {
        $configuredDays = (int)($this->settings['db_save_result_lifetime_days'] ?? 0);

        return $configuredDays > 0 ? $configuredDays : ResultStorageService::DEFAULT_LIFETIME_DAYS;
    }

    /**
     * Absolute URL to a stored run, so the same value works on the result page,
     * in the backend list view and in the mail.
     */
    private function buildSavedResultUrl(string $token): string
    {
        return $this->uriBuilder
            ->reset()
            ->setCreateAbsoluteUri(true)
            ->uriFor('savedResult', ['token' => $token]);
    }
}
