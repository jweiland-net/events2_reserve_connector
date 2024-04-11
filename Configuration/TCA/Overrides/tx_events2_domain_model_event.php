<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function () {
    $extendedColumns = [
        'release_date' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.release_date',
            'config' => [
                'type' => 'input',
                'size' => 7,
                'eval' => 'date',
                'checkbox' => 1,
                'default' => 0,
            ],
        ],
        'social_teaser' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.social_teaser',
            'config' => [
                'type' => 'text',
                'cols' => 30,
                'rows' => 4,
                'eval' => 'trim',
            ],
        ],
        'theater_details' => [
            'exclude' => true,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.theater_details',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 15,
                'default' => '',
                'softref' => 'rtehtmlarea_images,typolink_tag,images,email[subst],url',
                'enableRichtext' => true,
            ],
        ],
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('tx_events2_domain_model_event', $extendedColumns);
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'theater_details,--div--;LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.tab.social, release_date, social_teaser',
        '',
        'after:download_links'
    );

    $newEventColumns = [
        'deadline' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.deadline',
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
            'displayCond' => 'FIELD:registration_required:REQ:true',
            'description' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.reserve_period.description',
            'label' => 'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang_db.xlf:tx_events2_domain_model_event.reserve_period',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_reserve_domain_model_period',
                'foreign_field' => 'events2_event',
                'enableCascadingDelete' => true,
                'overrideChildTca' => [
                    'columns' => [
                        'date' => [
                            'config' => [
                                'readOnly' => true
                            ]
                        ],
                        'begin' => [
                            'config' => [
                                'readOnly' => true
                            ]
                        ],
                        'end' => [
                            'config' => [
                                'readOnly' => true
                            ]
                        ]
                    ],
                    'types' => [
                        '1' => [
                            'showitem' => '--palette--;;date,--palette--;;max_participants,--palette--;;booking_restrictions',
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
});
