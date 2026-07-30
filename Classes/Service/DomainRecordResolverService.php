<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use ProudNerds\PnQuestionnaire\Exception\UnconfiguredRecordTableException;
use TYPO3\CMS\Extbase\Mvc\RequestInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

/**
 * DomainRecordResolverService
 *
 * Resolves a `domain_record` result page to a frontend URL by delegating
 * rendering to the record's owning extension.
 *
 * The target plugin is configured in TypoScript under `settings.domain_record_target`:
 *
 * ```typoscript
 * plugin.tx_pnquestionnaire_questionnaire {
 *   settings {
 *     domain_record_target {
 *       pageUid     = 42
 *       extension   = News
 *       controller  = News
 *       action      = detail
 *       argument    = news
 *       plugin      = Pi1
 *     }
 *   }
 * }
 * ```
 *
 * The site package must also register the target table via a TCA override:
 * ```php
 * $GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config']['allowed']
 *     = 'tx_news_domain_model_news';
 * $GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config']['foreign_table']
 *     = 'tx_news_domain_model_news';
 * ```
 */
class DomainRecordResolverService
{
    public function __construct(private readonly UriBuilder $uriBuilder) {}

    /**
     * Build the frontend URL to the detail view of a domain record.
     *
     * The record table is derived from
     * `$GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config']['foreign_table']`,
     * which must be set by the site package via a TCA override.
     *
     * @param int                  $recordUid UID of the specific record
     * @param array<string, mixed> $settings  Plugin settings (from `$this->settings` in controller)
     * @param RequestInterface     $request   Current request (needed by UriBuilder)
     *
     * @throws UnconfiguredRecordTableException When foreign_table is missing or domain_record_target is unconfigured
     */
    public function resolveUri(
        int $recordUid,
        array $settings,
        RequestInterface $request
    ): string {
        $recordTable = $GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config']['foreign_table'] ?? '';

        if ($recordTable === '') {
            throw new UnconfiguredRecordTableException(
                'No foreign_table configured for tx_pnquestionnaire_result_page.record_uid. '
                . 'Add a $GLOBALS[\'TCA\'] override in your site package setting '
                . '[\'config\'][\'foreign_table\'] to the target record table.',
                1742940001
            );
        }

        $tableConfig = $settings['domain_record_target'] ?? null;

        if (!is_array($tableConfig)) {
            throw new UnconfiguredRecordTableException(
                'No domain_record_target configured in TypoScript. '
                . 'Add a block under plugin.tx_pnquestionnaire_questionnaire.settings.domain_record_target.',
                1742940000
            );
        }

        $this->uriBuilder->reset();
        $this->uriBuilder->setRequest($request);
        $this->uriBuilder->setTargetPageUid((int)($tableConfig['pageUid'] ?? 0));
        $this->uriBuilder->setCreateAbsoluteUri(true);

        return $this->uriBuilder->uriFor(
            $tableConfig['action']     ?? 'show',
            [$tableConfig['argument']  ?? 'record' => $recordUid],
            $tableConfig['controller'] ?? '',
            $tableConfig['extension']  ?? '',
            $tableConfig['plugin']     ?? ''
        );
    }

    /**
     * Return true when domain_record_target is configured in TypoScript
     * and foreign_table is set via TCA override.
     *
     * @param array<string, mixed> $settings
     */
    public function isTableConfigured(array $settings): bool
    {
        $recordTable = $GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config']['foreign_table'] ?? '';
        return $recordTable !== '' && is_array($settings['domain_record_target'] ?? null);
    }
}
