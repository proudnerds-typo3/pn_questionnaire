<?php

declare(strict_types=1);

defined('TYPO3') or die();

use ProudNerds\PnQuestionnaire\Controller\QuestionnaireController;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

ExtensionUtility::configurePlugin(
    'PnQuestionnaire',
    'Questionnaire',
    [
        QuestionnaireController::class => 'intro, question, process, result, savedResult, mailResult, reset',
    ],
    [
        QuestionnaireController::class => 'intro, question, process, result, savedResult, mailResult, reset',
    ],
    ExtensionUtility::PLUGIN_TYPE_CONTENT_ELEMENT
);

// On v12 the "new content element" wizard is built from page TSconfig alone, so a CType plugin
// never shows up there by itself. From v13 the core derives the wizard from the tt_content.CType
// items, which makes registerPlugin() above sufficient — and addPageTSConfig() no longer exists,
// hence the guard. Same approach as EXT:news.
if ((new Typo3Version())->getMajorVersion() < 13) {
    // @extensionScannerIgnoreLine
    ExtensionManagementUtility::addPageTSConfig(
        '@import \'EXT:pn_questionnaire/Configuration/TSconfig/ContentElementWizard.tsconfig\''
    );
}

// Mail templates for the result mail. Registered here rather than in TypoScript so
// they also resolve outside a frontend request, and under a string key instead of a
// number: a generic index like 10 would overwrite a path of another extension.
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['templateRootPaths']['pnQuestionnaire']
    = 'EXT:pn_questionnaire/Resources/Private/Templates/Email/';
// The whole partial folder, not a mail-only subfolder: the mail renders the same
// advice blocks as the result page and shares that partial with it.
$GLOBALS['TYPO3_CONF_VARS']['MAIL']['partialRootPaths']['pnQuestionnaire']
    = 'EXT:pn_questionnaire/Resources/Private/Partials/';

// Keep the token out of the cache hash, so the URL of a saved result stays valid
// for its whole lifetime: a cHash is derived from the installation encryption key,
// which would turn every stored and mailed link into a 404 the moment that key
// changes. Excluding it is safe because every action of this plugin is uncached,
// so the token never takes part in a cache identifier to begin with.
$GLOBALS['TYPO3_CONF_VARS']['FE']['cacheHash']['excludedParameters'][]
    = 'tx_pnquestionnaire_questionnaire[token]';
