<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Information\Typo3Version;

defined('TYPO3') or die();

// Visitor data, not editorial content — hence no language fields, no versioning and no manual
// sorting. The fields are read-only so an editor can inspect a stored run without altering it;
// the table itself stays writable so a record can be deleted and restored from the recycler.
return [
    'ctrl' => [
        'title' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result',
        'label' => 'token',
        'label_alt' => 'crdate',
        'label_alt_force' => true,
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'default_sortby' => 'crdate DESC',
        'delete' => 'deleted',
        'enablecolumns' => [],
        // v14 negeert deze sleutel en leidt zoekbaarheid af uit het veldtype;
        // op v12 en v13 is dit de enige manier om de backend-zoekvelden te bepalen.
        ...((new Typo3Version())->getMajorVersion() < 14 ? ['searchFields' => 'token,result_url'] : []),
        'typeicon_classes' => [
            'default' => 'tx-pnquestionnaire-saved-result',
        ],
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                token, crdate, expires, questionnaire, score, result_url,
            --div--;LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tabs.saved_answers,
                answers
        '],
    ],
    'columns' => [
        'crdate' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.crdate',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'token' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.token',
            'config' => [
                'type' => 'input',
                'size' => 40,
                'max' => 32,
                'readOnly' => true,
            ],
        ],
        'expires' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.expires',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.expires.description',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'questionnaire' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.questionnaire',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_pnquestionnaire_questionnaire',
                'minitems' => 0,
                'maxitems' => 1,
                'default' => 0,
                'readOnly' => true,
            ],
        ],
        'score' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.score',
            'config' => [
                'type' => 'number',
                'format' => 'decimal',
                'default' => 0,
                'readOnly' => true,
            ],
        ],
        'result_url' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.result_url',
            'description' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.result_url.description',
            'config' => [
                'type' => 'link',
            ],
        ],
        'answers' => [
            'label' => 'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_db.xlf:tx_pnquestionnaire_saved_result.answers',
            'config' => [
                'type' => 'json',
                'rows' => 12,
                'readOnly' => true,
            ],
        ],
    ],
];
