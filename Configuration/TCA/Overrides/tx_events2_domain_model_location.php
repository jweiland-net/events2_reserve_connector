<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

// Show only facilities from event location storage folder configured by PageTsConfig
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['facility']['config']['foreign_table_where']
    = 'AND (###PAGE_TSCONFIG_ID### = 0 OR tx_reserve_domain_model_facility.pid = ###PAGE_TSCONFIG_ID###) ORDER BY tx_reserve_domain_model_facility.name ASC';

// Add TCA description for editor to understand this field much better
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['facility']['description']
    = 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_location.facility.description';

// Add field information to facility to give editor a colorful status for better understanding
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['facility']['config']['fieldWizard']['checkFacility'] = [
    'renderType' => 'checkFacility',
];

// Set address columns as required. Ticket#20169963
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['street']['config']['eval'] = 'trim,required';
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['zip']['config']['eval'] = 'trim,required';
$GLOBALS['TCA']['tx_events2_domain_model_location']['columns']['city']['config']['eval'] = 'trim,required';
