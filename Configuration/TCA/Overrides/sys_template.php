<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addStaticFile(
    'events2_reserve_connector',
    'Configuration/TypoScript',
    'Events Reserve Connector Configurations'
);
