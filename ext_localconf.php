<?php

declare(strict_types=1);

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    // Synchronize events2 single events with reserve period
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \JWeiland\Events2ReserveConnector\Hooks\Events2ReserveSynchronization::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
        = \JWeiland\Events2ReserveConnector\Hooks\Events2ReserveSynchronization::class;
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['facility'] = 'tx_reserve_domain_model_facility';
});
