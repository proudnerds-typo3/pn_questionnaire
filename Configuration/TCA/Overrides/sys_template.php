<?php

declare(strict_types=1);

defined('TYPO3') or die();

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

// TYPO3 v12 does not support Site Sets, so the TypoScript is also offered as a
// selectable static template. On v13+ the Site Set is the preferred route; both
// point at the same files in Configuration/TypoScript/.
ExtensionManagementUtility::addStaticFile(
    'pn_questionnaire',
    'Configuration/TypoScript/',
    'Questionnaire / Test / Decision tree'
);
