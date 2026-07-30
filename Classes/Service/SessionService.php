<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * SessionService
 *
 * Stores and retrieves visitor answers for a questionnaire within the current
 * TYPO3 frontend session. Works for both authenticated and anonymous visitors —
 * TYPO3 creates an anonymous session record automatically on the first write.
 *
 * Session data structure (stored under the root key `tx_pnquestionnaire`):
 * ```
 * [
 *   'q_{questionnaireUid}' => [
 *     'answers' => [
 *       '{questionUid}' => ['{value}', ...],  // always an array of strings
 *     ],
 *   ],
 * ]
 * ```
 *
 * Answer values:
 * - Single-choice / Yes-No: array with one answer option UID string
 * - Multiple-choice: array of answer option UID strings
 * - Scale: array with one string representing the selected numeric value
 * - Informational: never stored (no user input)
 *
 * The `FrontendUserAuthentication` instance is passed as a method parameter
 * (not constructor-injected) because it is request-scoped. Retrieve it from
 * the controller via `$this->request->getAttribute('frontend.user')`.
 */
class SessionService
{
    /**
     * Usage counters that may be raised only once per run; see markCounted().
     */
    public const COUNTED_START = 'start';

    public const COUNTED_COMPLETION = 'completion';

    private const SESSION_KEY = 'tx_pnquestionnaire';

    /**
     * Persist one or more answer values for a question in the current session.
     *
     * @param array<string> $values Answer option UIDs (as strings) or a scale value
     */
    public function storeAnswer(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        int $questionUid,
        array $values
    ): void {
        $data = $this->loadData($feUser);
        $bucket = 'q_' . $questionnaireUid;

        $data[$bucket]['answers'][$questionUid] = array_values(
            array_map('strval', $values)
        );

        $feUser->setAndSaveSessionData(self::SESSION_KEY, $data);
    }

    /**
     * Return all stored answers for a questionnaire.
     *
     * @return array<int, array<string>> Map of questionUid → values[]
     */
    public function getAnswers(FrontendUserAuthentication $feUser, int $questionnaireUid): array
    {
        $data = $this->loadData($feUser);
        return $data['q_' . $questionnaireUid]['answers'] ?? [];
    }

    /**
     * Return the stored values for a single question, or an empty array if not yet answered.
     *
     * @return array<string>
     */
    public function getAnswer(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        int $questionUid
    ): array {
        return $this->getAnswers($feUser, $questionnaireUid)[$questionUid] ?? [];
    }

    /**
     * Return true when the visitor has stored at least one value for the given question.
     */
    public function hasAnswer(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        int $questionUid
    ): bool {
        return $this->getAnswer($feUser, $questionnaireUid, $questionUid) !== [];
    }

    /**
     * Remove all stored answers for a questionnaire (call on start or restart).
     */
    public function clearSession(FrontendUserAuthentication $feUser, int $questionnaireUid): void
    {
        $data = $this->loadData($feUser);
        unset($data['q_' . $questionnaireUid]);
        $feUser->setAndSaveSessionData(self::SESSION_KEY, $data);
    }

    /**
     * Replace the stored run for a questionnaire with the answers and token of a
     * result that was saved earlier, so opening the saved link continues that run
     * instead of starting a new one. Anything already in the session for this
     * questionnaire is discarded, and both values are written in one go so the
     * session never holds answers without their token.
     *
     * @param array<int|string, array<string>> $answers Map of questionUid → values[]
     */
    public function restoreRun(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        array $answers,
        string $token
    ): void {
        $restoredAnswers = [];
        foreach ($answers as $questionUid => $values) {
            if (!is_array($values)) {
                continue;
            }
            // JSON turns the integer question UIDs into strings; cast back so the
            // session holds the exact shape storeAnswer() would have written.
            $restoredAnswers[(int)$questionUid] = array_values(array_map('strval', $values));
        }

        $data = $this->loadData($feUser);
        $data['q_' . $questionnaireUid] = [
            'answers'     => $restoredAnswers,
            'resultToken' => $token,
            // Both counters start out as counted: this run was already counted when the
            // visitor completed it, and opening the saved link a year later is not a new
            // start and not a new completion.
            'counted'     => [
                self::COUNTED_START      => true,
                self::COUNTED_COMPLETION => true,
            ],
        ];

        $feUser->setAndSaveSessionData(self::SESSION_KEY, $data);
    }

    /**
     * Remember the token of the stored result for this questionnaire, so that
     * revisiting the result page updates that row instead of writing a second one.
     */
    public function storeResultToken(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        string $token
    ): void {
        $data = $this->loadData($feUser);
        $data['q_' . $questionnaireUid]['resultToken'] = $token;

        $feUser->setAndSaveSessionData(self::SESSION_KEY, $data);
    }

    /**
     * Return the token of the stored result for this questionnaire, or an empty
     * string when this run has not been stored yet.
     */
    public function getResultToken(FrontendUserAuthentication $feUser, int $questionnaireUid): string
    {
        $token = $this->loadData($feUser)['q_' . $questionnaireUid]['resultToken'] ?? '';

        return is_string($token) ? $token : '';
    }

    /**
     * Load the full session data array, defaulting to an empty array.
     *
     * @return array<string, mixed>
     */
    /**
     * Return true when the given usage counter has already been raised for this run.
     */
    public function hasCounted(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        string $counter
    ): bool {
        $data = $this->loadData($feUser);

        return (bool)($data['q_' . $questionnaireUid]['counted'][$counter] ?? false);
    }

    /**
     * Record that a usage counter has been raised for this run, so a refresh of the page
     * does not raise it a second time. Starting over clears the flags along with the
     * answers, which is intended: that is a new run.
     */
    public function markCounted(
        FrontendUserAuthentication $feUser,
        int $questionnaireUid,
        string $counter
    ): void {
        $data = $this->loadData($feUser);
        $data['q_' . $questionnaireUid]['counted'][$counter] = true;

        $feUser->setAndSaveSessionData(self::SESSION_KEY, $data);
    }

    private function loadData(FrontendUserAuthentication $feUser): array
    {
        /** @var array<string, mixed>|null $data */
        $data = $feUser->getSessionData(self::SESSION_KEY);
        return is_array($data) ? $data : [];
    }
}
