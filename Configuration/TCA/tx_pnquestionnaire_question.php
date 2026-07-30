<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

// Shared showitem for all choice-based question types (single, multiple, yes/no)
$choiceShowitem = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        type, question_text, help_text, tt_content_uid, required,
    --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.answers,
        answer_options,
    --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.conditions,
        conditions,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        sys_language_uid, l10n_parent, l10n_source
';

return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question',
        'label' => 'question_text',
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
        'enablecolumns' => [
            'disabled' => 'hidden',
        ],
        // v14 negeert deze sleutel en leidt zoekbaarheid af uit het veldtype;
        // op v12 en v13 is dit de enige manier om de backend-zoekvelden te bepalen.
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => 'question_text,help_text'] : []),
        'type' => 'type',
        'typeicon_column' => 'type',
        'typeicon_classes' => [
            'default' => 'tx-pnquestionnaire-question',
            'single_choice' => 'tx-pnquestionnaire-question-single-choice',
            'multiple_choice' => 'tx-pnquestionnaire-question-multiple-choice',
            'yes_no' => 'tx-pnquestionnaire-question-yes-no',
            'scale' => 'tx-pnquestionnaire-question-scale',
            'informational' => 'tx-pnquestionnaire-question-informational',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    // TCA types per question type control which fields/inline sections are shown
    'types' => [
        '0'               => ['showitem' => $choiceShowitem], // fallback for new unsaved records
        'single_choice'   => ['showitem' => $choiceShowitem],
        'multiple_choice' => ['showitem' => $choiceShowitem],
        'yes_no'          => ['showitem' => $choiceShowitem],
        'scale' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                type, question_text, help_text, tt_content_uid, required, scale_display, scale_min, scale_max,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.conditions,
                conditions,
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
                sys_language_uid, l10n_parent, l10n_source
        '],
        'informational' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                type, question_text, help_text, tt_content_uid,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.conditions,
                conditions,
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
                'allowed' => 'tx_pnquestionnaire_question',
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
        'questionnaire' => [
            'config' => ['type' => 'passthrough'],
        ],
        'sort_order' => [
            'config' => ['type' => 'passthrough'],
        ],
        'type' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type.single_choice',
                        'value' => 'single_choice',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type.multiple_choice',
                        'value' => 'multiple_choice',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type.yes_no',
                        'value' => 'yes_no',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type.scale',
                        'value' => 'scale',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.type.informational',
                        'value' => 'informational',
                    ],
                ],
                'default' => 'single_choice',
            ],
        ],
        'question_text' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.question_text',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'rows' => 5,
                'required' => true,
            ],
        ],
        'help_text' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.help_text',
            'config' => [
                'type' => 'text',
                'rows' => 3,
            ],
        ],
        'tt_content_uid' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.tt_content_uid',
            'config' => [
                'type' => 'group',
                'allowed' => 'tt_content',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
                'suggestOptions' => [
                    'default' => [
                        'searchWholePhrase' => true,
                    ],
                ],
            ],
        ],
        'scale_min' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.scale_min',
            'config' => [
                'type' => 'number',
                'default' => 1,
                'range' => ['lower' => 0, 'upper' => 100],
            ],
        ],
        'scale_max' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.scale_max',
            'config' => [
                'type' => 'number',
                'default' => 10,
                'range' => ['lower' => 1, 'upper' => 100],
            ],
        ],
        'scale_display' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.scale_display',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'range',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.scale_display.radio',
                        'value' => 'radio',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.scale_display.range',
                        'value' => 'range',
                    ],
                ],
            ],
        ],
        'required' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.required',
            'config' => [
                'type' => 'check',
                'renderType' => 'checkboxToggle',
                'default' => 1,
            ],
        ],
        'answer_options' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.answer_options',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_pnquestionnaire_answer_option',
                'foreign_field' => 'question',
                'foreign_sortby' => 'sort_order',
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => false,
                    'expandSingle' => false,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                    'enabledControls' => [
                        'dragdrop' => true,
                    ],
                ],
            ],
        ],
        'conditions' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_question.conditions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_pnquestionnaire_condition',
                'foreign_field' => 'question',
                'foreign_sortby' => 'sort_order',
                'maxitems' => 20,
                'appearance' => [
                    'collapseAll' => false,
                    'expandSingle' => false,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                ],
            ],
        ],
    ],
];
