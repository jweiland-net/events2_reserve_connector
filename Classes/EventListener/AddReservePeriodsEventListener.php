<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2-reserve-connector.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2ReserveConnector\EventListener;

use Doctrine\DBAL\Connection;
use JWeiland\Events2\Domain\Model\Day;
use JWeiland\Events2\Domain\Model\Event;
use JWeiland\Events2\Domain\Model\Location;
use JWeiland\Events2\Event\PostProcessFluidVariablesEvent;
use JWeiland\Events2\EventListener\AbstractControllerEventListener;
use JWeiland\Events2ReserveConnector\Traits\GetTreeListTrait;
use JWeiland\Reserve\Domain\Model\Facility;
use JWeiland\Reserve\Domain\Model\Period;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;

/**
 * With this EventListener we add the EXT:reserve period record to a specific event in Day->show template.
 */
class AddReservePeriodsEventListener extends AbstractControllerEventListener
{
    use GetTreeListTrait;

    protected array $allowedControllerActions = [
        'Day' => [
            'show',
        ],
    ];

    protected QueryFactoryInterface $queryFactory;

    public function __construct(QueryFactoryInterface $queryFactory)
    {
        $this->queryFactory = $queryFactory;
    }

    public function __invoke(PostProcessFluidVariablesEvent $event): void
    {
        if ($this->isValidRequest($event)) {
            $day = $event->getFluidVariables()['day'];
            if (!$day instanceof Day) {
                return;
            }

            $dateAndBegin = new \DateTime();
            $dateAndBegin->setTimestamp($day->getDayTimeAsTimestamp());
            $result = $this->findPeriods($dateAndBegin, $day->getEvent());
            $event->addFluidVariable(
                'periods',
                $this->findPeriods($dateAndBegin, $day->getEvent())
            );
        }
    }

    protected function findPeriods(\DateTime $dateTime, Event $event): QueryResultInterface
    {
        $date = clone $dateTime;
        $date->setTime(0, 0, 0);

        // $begin must be UTC because TCA saves that timestamp in UTC but others in configured timezone
        $begin = new \DateTime(sprintf('1970-01-01T%d:%d:%dZ', ...GeneralUtility::intExplode(':', $dateTime->format('H:i:s'))));
        $query = $this->createQuery($this->getStoragePagesForPeriods($event));
        $query->matching(
            $query->logicalAnd(...[
                $query->equals('facility', $this->getFacility($event)),
                $query->equals('events2Event', $event->getUid()),
                $query->equals('date', $date->getTimestamp()),
                $query->equals('begin', $begin->getTimestamp()),
            ])
        );

        return $query->execute();
    }

    protected function getStoragePagesForPeriods(Event $event): array
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        try {
            $site = $siteFinder->getSiteByPageId($event->getPid());
        } catch (SiteNotFoundException $siteNotFoundException) {
            // With pid = 0 there should be no results
            return [0];
        }

        $pagesOfPageTree = explode(',', $this->getTreeList($site->getRootPageId(), 10));

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $storagePage = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->in(
                    'pid',
                    $queryBuilder->createNamedParameter($pagesOfPageTree, Connection::PARAM_INT_ARRAY)
                ),
            )
            ->executeQuery()
            ->fetchAssociative();
        return is_array($storagePage) ? [$storagePage['uid']] : [0];
    }

    protected function getFacility(Event $event): ?Facility
    {
        /** @var \JWeiland\Events2ReserveConnector\Domain\Model\Location $location */
        $location = $event->getLocation();
        if (!$location instanceof Location) {
            return null;
        }

        $facility = $location->getFacility();
        if (!$facility instanceof Facility) {
            return null;
        }

        return $facility;
    }

    protected function createQuery(array $storagePids): QueryInterface
    {
        $query = $this->queryFactory->create(Period::class);
        $query->getQuerySettings()->setStoragePageIds($storagePids);

        return $query;
    }
}
