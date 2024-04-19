<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
    'tx_reserve_domain_model_period',
    [
        'events2_event' => [
            'config' => [
                'type' => 'group',
                'internal_type' => 'db',
                'allowed' => 'tx_events2_domain_model_event',
                'foreign_table' => 'tx_events2_domain_model_event',
                'minitems' => 0,
                'maxitems' => 1,
                'readOnly' => 1,
            ],
        ],
    ]
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'tx_reserve_domain_model_period',
    'events2_event'
);
