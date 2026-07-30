<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        // v14 negeert deze sleutel en leidt zoekbaarheid af uit het veldtype;
        // op v12 en v13 is dit de enige manier om de backend-zoekvelden te bepalen.
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => 'title'] : []),
        'typeicon_classes' => [
            'default' => 'tx-pnquestionnaire-questionnaire',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title, introduction_text,
                --palette--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:palette.usage;usage,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.questions,
                questions,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.result_pages,
                result_pages,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                hidden, starttime, endtime
        '],
    ],
    'palettes' => [
        'usage' => [
            'showitem' => 'starts, completions',
        ],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => [
                'type' => 'language',
            ],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_pnquestionnaire_questionnaire',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'l10n_source' => [
            'config' => ['type' => 'passthrough'],
        ],
        'l10n_diffsource' => [
            'config' => ['type' => 'passthrough', 'default' => ''],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:starttime_formlabel',
            'config' => ['type' => 'datetime'],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:endtime_formlabel',
            'config' => ['type' => 'datetime'],
        ],
        'title' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],
        'introduction_text' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.introduction_text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'rows' => 8,
            ],
        ],
        'questions' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.questions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_pnquestionnaire_question',
                'foreign_field' => 'questionnaire',
                'foreign_sortby' => 'sort_order',
                'maxitems' => 999,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                ],
            ],
        ],
        'result_pages' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.result_pages',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_pnquestionnaire_result_page',
                'foreign_field' => 'questionnaire',
                'foreign_sortby' => 'sort_order',
                'maxitems' => 999,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                ],
            ],
        ],
        // Raised by the frontend, never by an editor: read-only, and l10n_mode 'exclude'
        // so a translated record shows the numbers of the record that is actually counted.
        'starts' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.starts',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.starts.description',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
                'default' => 0,
            ],
        ],
        'completions' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.completions',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_questionnaire.completions.description',
            'l10n_mode' => 'exclude',
            'config' => [
                'type' => 'number',
                'readOnly' => true,
                'default' => 0,
            ],
        ],
    ],
];
