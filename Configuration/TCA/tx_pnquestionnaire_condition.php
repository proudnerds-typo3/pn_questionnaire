<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition',
        'label' => 'reference_question',
        'label_alt' => 'reference_answer',
        'label_alt_force' => true,
        'hideTable' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sort_order',
        'versioningWS' => true,
        'delete' => 'deleted',
        'enablecolumns' => [],
        // v14 negeert deze sleutel en leidt zoekbaarheid af uit het veldtype;
        // op v12 en v13 is dit de enige manier om de backend-zoekvelden te bepalen.
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => ''] : []),
        'typeicon_classes' => [
            'default' => 'tx-pnquestionnaire-condition',
        ],
        'type' => 'condition_type',
        'requestUpdate' => 'reference_question,condition_type',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '0'               => ['showitem' => 'condition_type, reference_question, reference_answer, operator'],
        ''                => ['showitem' => 'condition_type, reference_question, reference_answer, operator'],
        'specific_answer' => ['showitem' => 'condition_type, reference_question, reference_answer, operator'],
        'scale_range'     => ['showitem' => 'condition_type, reference_question, scale_operator, scale_value, operator'],
    ],
    'columns' => [
        'question' => [
            'config' => ['type' => 'passthrough'],
        ],
        'sort_order' => [
            'config' => ['type' => 'passthrough'],
        ],
        'condition_type' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.condition_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => 'specific_answer',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.condition_type.specific_answer',
                        'value' => 'specific_answer',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.condition_type.scale_range',
                        'value' => 'scale_range',
                    ],
                ],
            ],
        ],
        'reference_question' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.reference_question',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_question',
                'foreign_table_where' => 'AND tx_pnquestionnaire_question.questionnaire = (
                    SELECT questionnaire FROM tx_pnquestionnaire_question WHERE uid = ###REC_FIELD_question###
                ) AND tx_pnquestionnaire_question.deleted = 0
                ORDER BY tx_pnquestionnaire_question.sort_order ASC',
                'items' => [
                    ['label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:labels.pleaseSelect', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'reference_answer' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.reference_answer',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_answer_option',
                'foreign_table_where' => 'AND tx_pnquestionnaire_answer_option.question = ###REC_FIELD_reference_question###
                    AND tx_pnquestionnaire_answer_option.deleted = 0
                    ORDER BY tx_pnquestionnaire_answer_option.sort_order ASC',
                'items' => [
                    ['label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:labels.pleaseSelect', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
            ],
        ],
        'scale_operator' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'default' => '>=',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator.gte',
                        'value' => '>=',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator.lte',
                        'value' => '<=',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator.gt',
                        'value' => '>',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator.lt',
                        'value' => '<',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_operator.eq',
                        'value' => '=',
                    ],
                ],
            ],
        ],
        'scale_value' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.scale_value',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
        'operator' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.operator',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.operator.AND',
                        'value' => 'AND',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_condition.operator.OR',
                        'value' => 'OR',
                    ],
                ],
                'default' => 'AND',
            ],
        ],
    ],
];
