<?php

declare(strict_types=1);

if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    $EXT_KEY = 'events2_reserve_connector';

    // Extend EXT:event2 domain models
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['events2']['extender'][\JWeiland\Events2\Domain\Model\Event::class][$EXT_KEY] =
        \JWeiland\Events2ReserveConnector\Domain\Model\Event::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['events2']['extender'][\JWeiland\Events2\Domain\Model\Location::class][$EXT_KEY] =
        \JWeiland\Events2ReserveConnector\Domain\Model\Location::class;
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['events2']['extender'][\JWeiland\Events2\Domain\Model\Organizer::class][$EXT_KEY] =
        \JWeiland\Events2ReserveConnector\Domain\Model\Organizer::class;

    // Extend EXT:reserve domain models
    $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['reserve']['extender'][\JWeiland\Reserve\Domain\Model\Period::class][$EXT_KEY] =
        \JWeiland\Events2ReserveConnector\Domain\Model\Period::class;

    // Synchronize events2 single events with reserve period
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][]
        = \JWeiland\Events2ReserveConnector\Hooks\Events2ReserveSynchronization::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]
        = \JWeiland\Events2ReserveConnector\Hooks\Events2ReserveSynchronization::class;

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['livesearch']['facility'] = 'tx_reserve_domain_model_facility';

    $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\JWeiland\Events2\Domain\Model\Location::class] = [
        'className' => \JWeiland\Events2ReserveConnector\Domain\Model\Location::class,
    ];
});
