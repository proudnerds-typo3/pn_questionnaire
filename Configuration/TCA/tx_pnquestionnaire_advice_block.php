<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block',
        'label' => 'headline',
        'hideTable' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sort_order',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'translationSource' => 'l10n_source',
        'delete' => 'deleted',
        'enablecolumns' => [],
        // v14 negeert deze sleutel en leidt zoekbaarheid af uit het veldtype;
        // op v12 en v13 is dit de enige manier om de backend-zoekvelden te bepalen.
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => 'headline'] : []),
        'type' => 'condition_type',
        'typeicon_column' => 'condition_type',
        'typeicon_classes' => [
            'default'          => 'tx-pnquestionnaire-advice-block-always',
            'always'           => 'tx-pnquestionnaire-advice-block-always',
            'score_range'      => 'tx-pnquestionnaire-advice-block-score-range',
            'specific_answer'  => 'tx-pnquestionnaire-advice-block-specific-answer',
            'scale_range'      => 'tx-pnquestionnaire-advice-block-score-range',
            'group_header'     => 'tx-pnquestionnaire-advice-block-always',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        // Fallback for new records before condition_type is saved (value = 0)
        '0' => ['showitem' => '
            condition_type, group_header, headline, body_text,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        // TCA types per condition_type to show/hide conditional fields cleanly
        'always' => ['showitem' => '
            condition_type, group_header, headline, body_text,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        'score_range' => ['showitem' => '
            condition_type, score_min, score_max, group_header, headline, body_text,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        'specific_answer' => ['showitem' => '
            condition_type, trigger_answer, negate_condition, group_header, headline, body_text,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        'scale_range' => ['showitem' => '
            condition_type, trigger_question, trigger_scale_min, trigger_scale_max, group_header, headline, body_text,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        // A group heading carries no body text, and cannot itself sit inside a group:
        // both body_text and group_header are deliberately absent here.
        'group_header' => ['showitem' => '
            condition_type, headline,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'group',
                'allowed' => 'tx_pnquestionnaire_advice_block',
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
        'result_page' => [
            'config' => ['type' => 'passthrough'],
        ],
        'sort_order' => [
            'config' => ['type' => 'passthrough'],
        ],
        'condition_type' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type.always',
                        'value' => 'always',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type.score_range',
                        'value' => 'score_range',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type.specific_answer',
                        'value' => 'specific_answer',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type.scale_range',
                        'value' => 'scale_range',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.condition_type.group_header',
                        'value' => 'group_header',
                    ],
                ],
                'default' => 'always',
            ],
        ],
        'group_header' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.group_header',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.group_header.description',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_advice_block',
                // Only headings on the same result page, and never the record itself.
                'foreign_table_where' => 'AND tx_pnquestionnaire_advice_block.condition_type = \'group_header\'
                    AND tx_pnquestionnaire_advice_block.result_page = ###REC_FIELD_result_page###
                    AND tx_pnquestionnaire_advice_block.uid <> ###THIS_UID###
                    AND tx_pnquestionnaire_advice_block.deleted = 0
                    ORDER BY tx_pnquestionnaire_advice_block.sort_order ASC',
                'items' => [
                    ['label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:labels.pleaseSelect', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'headline' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.headline',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'body_text' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.body_text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'rows' => 8,
            ],
        ],
        'score_min' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.score_min',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 0,
            ],
        ],
        'score_max' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.score_max',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 0,
            ],
        ],
        'trigger_answer' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.trigger_answer',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_answer_option',
                'foreign_table_where' => 'AND tx_pnquestionnaire_answer_option.question IN (
                    SELECT q.uid FROM tx_pnquestionnaire_question q
                    INNER JOIN tx_pnquestionnaire_result_page rp ON rp.questionnaire = q.questionnaire
                    WHERE rp.uid = ###REC_FIELD_result_page### AND q.deleted = 0
                ) AND tx_pnquestionnaire_answer_option.deleted = 0
                ORDER BY tx_pnquestionnaire_answer_option.sort_order ASC',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'items' => [
                    ['label' => '–', 'value' => 0],
                ],
            ],
        ],
        'negate_condition' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.negate_condition',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.negate_condition.description',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 0,
            ],
        ],
        'trigger_question' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.trigger_question',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_question',
                'foreign_table_where' => 'AND tx_pnquestionnaire_question.type = \'scale\'
                    AND tx_pnquestionnaire_question.questionnaire IN (
                        SELECT questionnaire FROM tx_pnquestionnaire_result_page WHERE uid = ###REC_FIELD_result_page###
                    )
                    AND tx_pnquestionnaire_question.deleted = 0
                    ORDER BY tx_pnquestionnaire_question.sort_order ASC',
                'items' => [
                    ['label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:labels.pleaseSelect', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'trigger_scale_min' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.trigger_scale_min',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
        'trigger_scale_max' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_advice_block.trigger_scale_max',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
    ],
];
