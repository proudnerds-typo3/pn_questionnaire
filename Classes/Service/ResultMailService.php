<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Domain\Model\AdviceBlock;
use ProudNerds\PnQuestionnaire\Domain\Model\Questionnaire;
use ProudNerds\PnQuestionnaire\Domain\Model\ResultPage;
use ProudNerds\PnQuestionnaire\Domain\Model\SavedResult;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MailUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Mails the outcome of a run to an address the visitor typed in.
 *
 * The mail carries the full outcome text as well as the saved link: the text makes
 * it readable on its own, the link stays the current source and the way back in to
 * change the answers. The address is used to address the message and is deliberately
 * neither stored nor logged.
 */
class ResultMailService
{
    /**
     * Resolved against MAIL.templateRootPaths, where the extension registers its own
     * folder. FluidEmail renders both SavedResult.html and SavedResult.txt from it.
     */
    private const TEMPLATE_NAME = 'SavedResult';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
    ) {}

    /**
     * @param array<AdviceBlock>   $adviceBlocks The blocks the visitor sees, already filtered
     * @param array<string, mixed> $settings     Plugin settings; the mail keys are read here
     * @return bool False when the transport refused the message, so the caller can say so
     */
    public function sendSavedResult(
        string $recipient,
        ServerRequestInterface $request,
        Questionnaire $questionnaire,
        ResultPage $resultPage,
        array $adviceBlocks,
        SavedResult $savedResult,
        array $settings = []
    ): bool {
        $introText  = trim((string)($settings['mail_intro_text'] ?? ''));
        $footerText = trim((string)($settings['mail_footer_text'] ?? ''));

        $email = GeneralUtility::makeInstance(FluidEmail::class);
        $email
            ->setRequest($request)
            ->to(new Address($recipient))
            ->subject($this->resolveSubject($questionnaire->getTitle()))
            ->format(FluidEmail::FORMAT_BOTH)
            ->setTemplate(self::TEMPLATE_NAME)
            ->assignMultiple([
                'questionnaire' => $questionnaire,
                'resultPage'    => $resultPage,
                'adviceBlocks'  => $adviceBlocks,
                'savedResult'   => $savedResult,
                // Editor-supplied rich text; the template falls back to a translated
                // default when a field is left empty
                'introText'     => $introText,
                'footerText'    => $footerText,
                'privacyLink'   => trim((string)($settings['privacy_link'] ?? '')),
                // Plain-text counterparts: the text variant needs the line breaks that
                // live in the markup, and Fluid has no way to turn tags into newlines
                'introTextPlain'  => $this->toPlainText($introText),
                'footerTextPlain' => $this->toPlainText($footerText),
                'bodyTextPlain'   => $this->toPlainText($resultPage->getBodyText()),
                'blocksPlain'     => array_map(
                    fn(AdviceBlock $block): array => [
                        'headline' => $block->getHeadline(),
                        'body'     => $this->toPlainText($block->getBodyText()),
                    ],
                    array_values($adviceBlocks)
                ),
            ]);

        // Only set a sender when one is configured: the mailer fills in
        // MAIL.defaultMailFromAddress and -Name of the installation otherwise,
        // which is exactly the intended fallback.
        $fromAddress = trim((string)($settings['mail_from_address'] ?? ''));
        $fromName    = trim((string)($settings['mail_from_name'] ?? ''));

        // A name without an address is a legitimate configuration — the installation
        // already has a sending address, only no display name. Without this the name
        // would be dropped without a word.
        if ($fromAddress === '' && $fromName !== '') {
            $fromAddress = (string)MailUtility::getSystemFromAddress();
        }

        if ($fromAddress !== '') {
            $email->from($fromName !== '' ? new Address($fromAddress, $fromName) : new Address($fromAddress));
        }

        try {
            $this->mailer->send($email);
        } catch (TransportExceptionInterface $e) {
            // Transports tend to echo the recipient in their error text, and that is
            // the one personal datum in this flow — masked so a misconfigured mail
            // setup stays diagnosable without the address ending up in the log.
            $this->logger->error(
                'pn_questionnaire: Sending the result mail failed — '
                . str_replace($recipient, '***', $e->getMessage())
            );

            return false;
        }

        return true;
    }

    /**
     * Turn rich text into readable plain text.
     *
     * Stripping tags on its own runs paragraphs and line breaks together — "Kind
     * regards,Municipality" — because the breaks only exist in the markup. So the
     * block-level tags become newlines first. Link targets are lost either way; the
     * plain-text mail carries the saved link separately for that reason.
     */
    private function toPlainText(string $html): string
    {
        $withBreaks = preg_replace(
            '#</(p|div|li|h[1-6]|tr)>|<br\s*/?>#i',
            "\n",
            $html
        ) ?? $html;

        $text = html_entity_decode(strip_tags($withBreaks), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Collapse the run of blank lines that closing tags on their own lines leave behind
        return trim((string)preg_replace("#\n{3,}#", "\n\n", $text));
    }

    /**
     * From the language files rather than from a setting, so no visitor-supplied text
     * can ever reach the subject line. Names the questionnaire, because "Your result"
     * on its own says nothing in an inbox.
     */
    private function resolveSubject(string $questionnaireTitle): string
    {
        return LocalizationUtility::translate('mail.subject', 'PnQuestionnaire', [$questionnaireTitle])
            ?? 'Your result of ' . $questionnaireTitle;
    }

}
