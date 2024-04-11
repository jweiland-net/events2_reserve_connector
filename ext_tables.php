<?php

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(function () {
    // Reload form to show INLINE for timeslot of EXT:reserve
    $GLOBALS['TCA']['tx_events2_domain_model_event']['columns']['registration_required']['onChange'] = 'reload';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'tx_events2_domain_model_event',
        'reserve_period',
        'single',
        'after:registration_required'
    );
});
