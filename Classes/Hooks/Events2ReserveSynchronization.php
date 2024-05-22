<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2-reserve-connector.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2ReserveConnector\Hooks;

use JWeiland\Events2ReserveConnector\Exception\FacilityNotFoundException;
use JWeiland\Events2ReserveConnector\Traits\GetTreeListTrait;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

class Events2ReserveSynchronization
{
    use GetTreeListTrait;

    private const EVENTS2_EVENT_TABLE = 'tx_events2_domain_model_event';
    private const EVENTS2_LOCATION_TABLE = 'tx_events2_domain_model_location';
    private const EVENTS2_TIME_TABLE = 'tx_events2_domain_model_time';
    private const RESERVE_PERIOD_TABLE = 'tx_reserve_domain_model_period';

    private FlashMessageQueue $flashMessageQueue;

    public function __construct()
    {
        $this->flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)
            ->getMessageQueueByIdentifier();
    }

    /**
     * Synchronize current single event with a reserve period. Updates an existing period if field "events2_event"
     * === current event uid, otherwise creates a new period.
     */
    public function processDatamap_beforeStart(DataHandler $dataHandler): void
    {
        if (!array_key_exists(self::EVENTS2_EVENT_TABLE, $dataHandler->datamap)) {
            return;
        }

        try {
            foreach ($dataHandler->datamap[self::EVENTS2_EVENT_TABLE] as $eventUid => $eventFromRequest) {
                $eventRecord = $this->getMergedEventRecord($eventUid, $eventFromRequest);
                if (
                    !array_key_exists('event_type', $eventRecord)
                    || $eventRecord['event_type'] !== 'single'
                    || !array_key_exists('registration_required', $eventRecord)
                ) {
                    continue;
                }

                // If registration was explicitly disabled, register related periods to be deleted
                if (
                    !$eventRecord['registration_required']
                    && MathUtility::canBeInterpretedAsInteger($eventUid)
                ) {
                    $this->removeRelatedPeriods($dataHandler, (int)$eventUid);
                    continue;
                }

                // If record is NEW and registration is NOT activated: do nothing
                if (!$eventRecord['registration_required']) {
                    continue;
                }
                $freshPeriodData = $this->buildFreshPeriod(
                    $this->getMergedEventTimeRecord($dataHandler, $eventRecord),
                    $this->findRelatedFacilityUid($eventRecord['location']),
                    $eventRecord,
                    $eventUid
                );
                if (\str_starts_with((string)$eventUid, 'NEW')) {
                    // Create new period record with all possible record data
                    $freshPeriodData['pid'] = $this->getPidForPeriodRecord((int)$eventRecord['pid']);
                    $this->addPeriodToDatamap($dataHandler, StringUtility::getUniqueId('NEW'), $freshPeriodData);
                } elseif ($uidOfRelatedPeriodRecord = $this->getUidOfRelatedPeriodRecord((int)$eventUid)) {
                    // Update period record. In that case only date, begin and end columns have to be updated.
                    $this->addPeriodToDatamap(
                        $dataHandler,
                        $uidOfRelatedPeriodRecord,
                        array_intersect_key($freshPeriodData, ['date' => 1, 'begin' => 1, 'end' => 1, 'facility' => 1])
                    );
                } else {
                    // Period was deleted in the meanwhile. Create a new one.
                    $freshPeriodData['pid'] = $this->getPidForPeriodRecord((int)$eventRecord['pid']);
                    $this->addPeriodToDatamap($dataHandler, StringUtility::getUniqueId('NEW'), $freshPeriodData);
                }
            }
        } catch (FacilityNotFoundException $facilityNotFoundException) {
            $this->flashMessageQueue->enqueue(
                new FlashMessage(
                    $GLOBALS['LANG']->sL(
                        'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang.xlf:events2ReserveSynchronization.error.facilityNotFound.description'
                    ),
                    $GLOBALS['LANG']->sL(
                        'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang.xlf:events2ReserveSynchronization.error.facilityNotFound.title'
                    ),
                    AbstractMessage::ERROR
                )
            );
        }
    }

    public function processCmdmap_beforeStart(DataHandler $dataHandler): void
    {
        if (array_key_exists(self::EVENTS2_EVENT_TABLE, $dataHandler->cmdmap)) {
            foreach ($dataHandler->cmdmap[self::EVENTS2_EVENT_TABLE] as $eventUid => $eventFromRequest) {
                if (MathUtility::canBeInterpretedAsInteger($eventUid)) {
                    $this->removeRelatedPeriods($dataHandler, (int)$eventUid);
                }
            }
        }
    }

    /**
     * While dynamically creating a new period record above, we have set "events2_event" column of period table to
     * event UID "NEW[hash]". This relation will be resolved to a valid int UID and updated in DB in
     * $dataHandler->processRemapStack(). It also stores that the period record has a relation to the event record
     * and marks event record as child record of period record. See: $dataHandler->newRelatedIDs[].
     * This hook was called AFTER "processRemapStack", so we can remove event record as child record from period record.
     * Without this modification the EditDocumentController will not switch from command "new" to "edit" which
     * will result in a "Create new Event on page X" form instead of edit view.
     */
    public function processDatamap_afterAllOperations(DataHandler $dataHandler): void
    {
        unset($dataHandler->newRelatedIDs[self::EVENTS2_EVENT_TABLE]);
    }

    private function removeRelatedPeriods(DataHandler $dataHandler, int $eventUid): void
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::RESERVE_PERIOD_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $reservePeriodResult = $queryBuilder
            ->select('uid')
            ->from(self::RESERVE_PERIOD_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'events2_event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery();

        while ($periodRecord = $reservePeriodResult->fetchAssociative()) {
            $this->registerPeriodInCmdMapToBeRemoved($dataHandler, (int)$periodRecord['uid']);
        }
    }

    /**
     * @param string|int $periodUid String, if NEW
     * @throws Exception
     */
    private function addPeriodToDatamap(DataHandler $dataHandler, $periodUid, array $periodData): void
    {
        $periodFromRequest = [];
        if (isset($dataHandler->datamap[self::RESERVE_PERIOD_TABLE][$periodUid]) &&
            is_array($dataHandler->datamap[self::RESERVE_PERIOD_TABLE][$periodUid])
        ) {
            $periodFromRequest = $dataHandler->datamap[self::RESERVE_PERIOD_TABLE][$periodUid];
        }

        $dataHandler->datamap[self::RESERVE_PERIOD_TABLE][$periodUid] = array_merge(
            $periodFromRequest,
            $periodData
        );

        $this->flashMessageQueue->enqueue(
            new FlashMessage(
                $GLOBALS['LANG']->sL(
                    'LLL:EXT:events2_reserve_connector/Resources/Private/Language/locallang.xlf:events2ReserveSynchronization.success.synchronizedEvent'
                ),
                '',
                AbstractMessage::OK
            )
        );
    }

    private function registerPeriodInCmdMapToBeRemoved(DataHandler $dataHandler, int $periodUid): void
    {
        $dataHandler->cmdmap[self::RESERVE_PERIOD_TABLE][$periodUid] = [
            'delete' => 1,
        ];
    }

    private function getMergedEventRecord($uid, array $eventRecordFromRequest): array
    {
        if (!MathUtility::canBeInterpretedAsInteger($uid)) {
            return $eventRecordFromRequest;
        }

        $eventRecord = BackendUtility::getRecord(
            self::EVENTS2_EVENT_TABLE,
            (int)$uid,
            'uid, pid, hidden, tstamp, event_type, event_begin, event_time, registration_required, location'
        ) ?: [];

        ArrayUtility::mergeRecursiveWithOverrule(
            $eventRecord,
            $eventRecordFromRequest
        );

        return $eventRecord;
    }

    protected function getMergedEventTimeRecord(DataHandler $dataHandler, array $eventRecord): array
    {
        $eventTimeRecord = [];
        $eventTimeRecordFromRequest = $dataHandler->datamap[self::EVENTS2_TIME_TABLE][$eventRecord['event_time']] ?? [];

        if (array_key_exists('event_time', $eventRecord)) {
            $eventTimeRecord = $eventTimeRecordFromRequest;

            if (MathUtility::canBeInterpretedAsInteger($eventRecord['event_time'])) {
                $eventTimeRecord = BackendUtility::getRecord(
                    self::EVENTS2_TIME_TABLE,
                    (int)$eventRecord['event_time'],
                    'time_begin, time_end'
                ) ?? [];
                ArrayUtility::mergeRecursiveWithOverrule($eventTimeRecord, $eventTimeRecordFromRequest);
            }
        }

        return $eventTimeRecord;
    }

    private function getUidOfRelatedPeriodRecord(int $eventUid): int
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::RESERVE_PERIOD_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $record = $queryBuilder
            ->select('uid')
            ->from(self::RESERVE_PERIOD_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'events2_event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];

        return array_key_exists('uid', $record) && $record['uid'] ? (int)$record['uid'] : 0;
    }

    /**
     * @param string|int $eventLocation
     * @throws FacilityNotFoundException
     */
    private function findRelatedFacilityUid($eventLocation): int
    {
        $locationUid = (int)str_replace(self::EVENTS2_LOCATION_TABLE . '_', '', $eventLocation);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::EVENTS2_LOCATION_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $facilityUid = $queryBuilder
            ->select('facility')
            ->from(self::EVENTS2_LOCATION_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($locationUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchOne();

        if (!$facilityUid) {
            throw new FacilityNotFoundException('Could not find a related facility!', 1634739383);
        }

        return $facilityUid;
    }

    /**
     * @param string|int $eventUid
     */
    private function getEventTimeByEventUid($eventUid): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable(self::EVENTS2_TIME_TABLE);
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder
            ->select('time_begin', 'time_end')
            ->from(self::EVENTS2_TIME_TABLE)
            ->where(
                $queryBuilder->expr()->eq(
                    'event',
                    $queryBuilder->createNamedParameter($eventUid, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative() ?: [];
    }

    private function buildFreshPeriod(array $timeRecord, int $facilityUid, array $eventRecord, $eventUid): array
    {
        // Adapted from DataHandler::checkValue_input_Eval()
        $timestamp = (int)$eventRecord['event_begin'];
        if (!MathUtility::canBeInterpretedAsInteger($eventRecord['event_begin'])) {
            $timestamp = (new \DateTime(
                $eventRecord['event_begin'],
                new \DateTimeZone(date_default_timezone_get())
            ))->getTimestamp();
            $timestamp -= date('Z', $timestamp);
        }

        return [
            'hidden' => $eventRecord['hidden'],
            'tstamp' => $eventRecord['tstamp'],
            'facility' => $facilityUid,
            'date' => $timestamp,
            'begin' => $timeRecord['time_begin'] ? $this->convertTimeToTimestamp($timeRecord['time_begin']) : 0,
            'end' => $timeRecord['time_end'] ? $this->convertTimeToTimestamp($timeRecord['time_end']) : 86400,
            'max_participants' => 50,
            'max_participants_per_order' => 5,
            'booking_begin' => $timestamp - (60 * 60 * 24 * 3),
            'booking_end' => $timestamp - (60 * 60 * 24 * 1),
            'events2_event' => $eventUid,
        ];
    }

    private function convertTimeToTimestamp(string $time): int
    {
        $timestamp = 0;
        if (preg_match('/\d\d:\d\d/', $time)) {
            $dateTime = sprintf(
                '1970-01-01T%s:00Z',
                $time
            );
            try {
                $date = new \DateTime($dateTime);
                $timestamp = $date->getTimestamp();
            } catch (\Exception $e) {
            }
        }

        return $timestamp;
    }

    private function getPidForPeriodRecord(int $pageUid): int
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $rootPageUid = $siteFinder->getSiteByPageId($pageUid)->getRootPageId();

        $csvPages = $this->getTreeList($rootPageUid, 99);

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $pageUid = (int)$queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'uid',
                    $queryBuilder->createNamedParameter(
                        explode(',', $csvPages),
                        Connection::PARAM_INT_ARRAY
                    ),
                ),
            )
            ->executeQuery()
            ->fetchOne();

        if ($pageUid === 0) {
            throw new \RuntimeException(
                'Could not find reserve storage folder for root page id: ' . $rootPageUid,
                1634739383
            );
        }

        return $pageUid;
    }

    protected function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
