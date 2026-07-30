<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

/**
 * Icon registry configuration for pn_questionnaire
 * Using Tabler Icons (https://tabler.io/icons)
 */
return [
    // Extension icon — canonical location per TYPO3 best practice
    // EXT:pn_questionnaire/Resources/Public/Icons/Extension.svg
    'tx-pnquestionnaire-ext-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/Extension.svg',
    ],

    // Plugin icon
    'tx-pnquestionnaire-plugin' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/route.svg',
    ],

    // Questionnaire record
    'tx-pnquestionnaire-questionnaire' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/clipboard-list.svg',
    ],

    // Question — default and per type
    'tx-pnquestionnaire-question' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/help-circle.svg',
    ],
    'tx-pnquestionnaire-question-single-choice' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/circle-dot.svg',
    ],
    'tx-pnquestionnaire-question-multiple-choice' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/checkbox.svg',
    ],
    'tx-pnquestionnaire-question-yes-no' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/toggle-left.svg',
    ],
    'tx-pnquestionnaire-question-scale' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/adjustments-horizontal.svg',
    ],
    'tx-pnquestionnaire-question-informational' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/info-circle.svg',
    ],

    // Answer Option
    'tx-pnquestionnaire-answer-option' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/list-check.svg',
    ],

    // Condition
    'tx-pnquestionnaire-condition' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/git-branch.svg',
    ],

    // Result Page
    'tx-pnquestionnaire-result-page' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/flag-check.svg',
    ],

    // Saved Result — an anonymously stored questionnaire run
    'tx-pnquestionnaire-saved-result' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/device-floppy.svg',
    ],

    // Advice Block — per condition_type
    'tx-pnquestionnaire-advice-block-always' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/bulb.svg',
    ],
    'tx-pnquestionnaire-advice-block-score-range' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/calculator.svg',
    ],
    'tx-pnquestionnaire-advice-block-specific-answer' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:pn_questionnaire/Resources/Public/Icons/message-check.svg',
    ],
];
