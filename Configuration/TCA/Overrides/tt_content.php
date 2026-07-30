<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

$pluginSignature = ExtensionUtility::registerPlugin(
    'PnQuestionnaire',
    'Questionnaire',
    'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_be.xlf:plugin.questionnaire.title',
    'tx-pnquestionnaire-plugin',
    'plugins',
    'LLL:EXT:pn_questionnaire/Resources/Private/Language/locallang_be.xlf:plugin.questionnaire.description',
);

ExtensionManagementUtility::addToAllTCAtypes('tt_content', '--div--;Configuration,pi_flexform,', $pluginSignature, 'after:subheader');

ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:pn_questionnaire/Configuration/FlexForms/Questionnaire.xml',
    $pluginSignature
);
