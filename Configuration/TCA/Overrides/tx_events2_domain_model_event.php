<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    $newEventColumns = [
        'deadline' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:' .
                'tx_events2_domain_model_event.deadline',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 7,
                'default' => 0,
                'eval' => 'date,int',
            ],
        ],
        'reserve_period' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:' .
                'tx_events2_domain_model_event.reserve_period',
            'description' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:' .
                'tx_events2_domain_model_event.reserve_period.description',
            'displayCond' => 'FIELD:registration_required:REQ:true',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_reserve_domain_model_period',
                'foreign_field' => 'events2_event',
                'enableCascadingDelete' => true,
                'overrideChildTca' => [
                    'columns' => [
                        'date' => [
                            'config' => [
                                'readOnly' => true,
                            ],
                        ],
                        'begin' => [
                            'config' => [
                                'readOnly' => true,
                            ],
                        ],
                        'end' => [
                            'config' => [
                                'readOnly' => true,
                            ],
                        ],
                    ],
                    'types' => [
                        '1' => [
                            'showitem' => '--palette--;;date,' .
                                '--palette--;;max_participants,' .
                                '--palette--;;booking_restrictions',
                        ],
                    ],
                ],
                'maxsize' => 1,
                'minsize' => 0,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns(
        'tx_events2_domain_model_event',
        $newEventColumns
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'deadline',
        '',
        'after:free_entry'
    );

    // move location/organizer to another position
    $types = ['single', 'recurring', 'duration'];
    $fields = [' location', ' organizer'];
    foreach ($types as $type) {
        $configList = $GLOBALS['TCA']['tx_events2_domain_model_event']['types'][$type]['showitem'];
        foreach ($fields as $field) {
            $updatedShowItems = implode(
                ',',
                array_filter(explode(',', $configList), static function ($item) use ($field): bool {
                    return $field !== $item;
                })
            );
            $GLOBALS['TCA']['tx_events2_domain_model_event']['types'][$type]['showitem'] = $updatedShowItems;
        }
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'location',
        'single,duration',
        'after:event_time'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'location',
        'recurring',
        'after:l10n_parent'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'organizer',
        '',
        'after:location'
    );

    // activate all checkboxes for "xth" and "weekday" as default
    $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['xth']['config']['default'] = 31;
    $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['weekday']['config']['default'] = 127;

    // Reload form to show INLINE for timeslot of EXT:reserve
    $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['registration_required']['onChange'] = 'reload';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'reserve_period',
        'single',
        'after:registration_required'
    );
});
