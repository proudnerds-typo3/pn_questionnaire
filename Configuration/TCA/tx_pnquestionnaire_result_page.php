<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page',
        'label' => 'title',
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
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => 'title,headline'] : []),
        'typeicon_classes' => [
            'default' => 'tx-pnquestionnaire-result-page',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                title,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.trigger,
                trigger_type, score_min, score_max, trigger_answer, trigger_question, trigger_scale_min, trigger_scale_max,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.outcome,
                outcome_type, headline, body_text, cta_label, cta_link, advice_blocks,
                page_uid, external_url, record_uid,
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
                'allowed' => 'tx_pnquestionnaire_result_page',
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
        'questionnaire' => [
            'config' => ['type' => 'passthrough'],
        ],
        'sort_order' => [
            'config' => ['type' => 'passthrough'],
        ],
        'title' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.title',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
                'required' => true,
            ],
        ],

        // --- Trigger fields ---
        'trigger_type' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type.catch_all',
                        'value' => 'catch_all',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type.score_range',
                        'value' => 'score_range',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type.specific_answer',
                        'value' => 'specific_answer',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type.combination',
                        'value' => 'combination',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_type.scale_answer',
                        'value' => 'scale_answer',
                    ],
                ],
                'default' => 'catch_all',
            ],
        ],
        'score_min' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.score_min',
            'displayCond' => 'FIELD:trigger_type:IN:score_range,combination',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 0,
            ],
        ],
        'score_max' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.score_max',
            'displayCond' => 'FIELD:trigger_type:IN:score_range,combination',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 0,
            ],
        ],
        'trigger_answer' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_answer',
            'displayCond' => 'FIELD:trigger_type:IN:specific_answer,combination',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_answer_option',
                'foreign_table_where' => 'AND tx_pnquestionnaire_answer_option.question IN (
                    SELECT uid FROM tx_pnquestionnaire_question
                    WHERE questionnaire = ###REC_FIELD_questionnaire### AND deleted = 0
                ) AND tx_pnquestionnaire_answer_option.deleted = 0
                ORDER BY tx_pnquestionnaire_answer_option.sort_order ASC',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'items' => [
                    ['label' => '– ' . 'None' . ' –', 'value' => 0],
                ],
            ],
        ],
        'trigger_question' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_question',
            'displayCond' => 'FIELD:trigger_type:=:scale_answer',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_question',
                'foreign_table_where' => 'AND tx_pnquestionnaire_question.type = \'scale\'
                    AND tx_pnquestionnaire_question.questionnaire = ###REC_FIELD_questionnaire###
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
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_scale_min',
            'displayCond' => 'FIELD:trigger_type:=:scale_answer',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],
        'trigger_scale_max' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.trigger_scale_max',
            'displayCond' => 'FIELD:trigger_type:=:scale_answer',
            'config' => [
                'type' => 'number',
                'default' => 0,
            ],
        ],

        // --- Outcome fields ---
        'outcome_type' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.outcome_type',
            'onChange' => 'reload',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.outcome_type.inline',
                        'value' => 'inline',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.outcome_type.internal_page',
                        'value' => 'internal_page',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.outcome_type.external_url',
                        'value' => 'external_url',
                    ],
                    [
                        'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.outcome_type.domain_record',
                        'value' => 'domain_record',
                    ],
                ],
                'default' => 'inline',
            ],
        ],
        'headline' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.headline',
            'displayCond' => 'FIELD:outcome_type:=:inline',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 255,
            ],
        ],
        'body_text' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.body_text',
            'displayCond' => 'FIELD:outcome_type:=:inline',
            'config' => [
                'type' => 'text',
                'enableRichtext' => true,
                'rows' => 10,
            ],
        ],
        'cta_label' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.cta_label',
            'displayCond' => 'FIELD:outcome_type:=:inline',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'max' => 255,
            ],
        ],
        'cta_link' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.cta_link',
            'displayCond' => 'FIELD:outcome_type:=:inline',
            'config' => [
                'type' => 'link',
            ],
        ],
        'advice_blocks' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.advice_blocks',
            'displayCond' => 'FIELD:outcome_type:=:inline',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_pnquestionnaire_advice_block',
                'foreign_field' => 'result_page',
                'foreign_sortby' => 'sort_order',
                'maxitems' => 99,
                'appearance' => [
                    // Start collapsed — a result page can hold dozens of blocks — but let the
                    // editor keep several open at once while comparing conditions.
                    'collapseAll' => true,
                    'expandSingle' => false,
                    'newRecordLinkAddTitle' => true,
                    'levelLinksPosition' => 'bottom',
                    'useSortable' => true,
                ],
            ],
        ],
        'page_uid' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.page_uid',
            'displayCond' => 'FIELD:outcome_type:=:internal_page',
            'config' => [
                'type' => 'group',
                'allowed' => 'pages',
                'size' => 1,
                'maxitems' => 1,
                'minitems' => 0,
                'default' => 0,
            ],
        ],
        'external_url' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.external_url',
            'displayCond' => 'FIELD:outcome_type:=:external_url',
            'config' => [
                'type' => 'input',
                'size' => 50,
                'max' => 2048,
                'eval' => 'trim',
            ],
        ],
        'record_uid' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.record_uid',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_result_page.record_uid.description',
            'displayCond' => 'FIELD:outcome_type:=:domain_record',
            'config' => [
                // Plain uid by default: this extension does not know which table a site
                // links to, and `type => 'group'` throws when `allowed` is empty — which
                // would break the whole result page form, not just this field.
                //
                // A site package that wants a record browser here overrides all three:
                //
                //   $c = &$GLOBALS['TCA']['tx_pnquestionnaire_result_page']['columns']['record_uid']['config'];
                //   $c['type'] = 'group';
                //   $c['allowed'] = 'tx_news_domain_model_news';
                //   $c['foreign_table'] = 'tx_news_domain_model_news';
                //   $c['size'] = 1;
                //
                // foreign_table is what DomainRecordResolverService checks, so without it
                // the domain_record outcome stays inert whichever type is configured.
                'type' => 'number',
                'default' => 0,
            ],
        ],
    ],
];
