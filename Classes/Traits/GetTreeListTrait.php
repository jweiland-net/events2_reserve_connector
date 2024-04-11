<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/events2-reserve-connector.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\Events2ReserveConnector\Traits;

use Doctrine\DBAL\Result;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Trait to retrieve all page UIDs recursively
 *
 * QueryGenerator and QueryView are deprecated since TYPO3 11, so we have created our own implementation
 */
trait GetTreeListTrait
{
    public function getTreeList(int $pageUid, int $depth, int $begin = 0, string $permClause = ''): string
    {
        if ($pageUid < 0) {
            $pageUid = (int)abs($pageUid);
        }

        if ($begin === 0) {
            $theList = (string)$pageUid;
        } else {
            $theList = '';
        }

        if ($pageUid && $depth > 0) {
            $queryResult = $this->getQueryResultForSubPages($pageUid, $permClause);
            while ($pageRecord = $queryResult->fetchAssociative()) {
                if ($begin <= 0) {
                    $theList .= ',' . $pageRecord['uid'];
                }
                if ($depth > 1) {
                    $theSubList = $this->getTreeList($pageRecord['uid'], $depth - 1, $begin - 1, $permClause);
                    if ($theList !== '' && $theSubList !== '' && ($theSubList[0] !== ',')) {
                        $theList .= ',';
                    }

                    $theList .= $theSubList;
                }
            }
        }

        return $theList;
    }

    private function getQueryResultForSubPages(int $pageUid, string $permClause): Result
    {
        $queryBuilder = $this->getQueryBuilderForPages();
        $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_language_uid',
                    0
                )
            )
            ->orderBy('uid');

        if ($permClause !== '') {
            $queryBuilder->andWhere(QueryHelper::stripLogicalOperatorPrefix($permClause));
        }

        return $queryBuilder->executeQuery();
    }

    private function getQueryBuilderForPages(): QueryBuilder
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        return $queryBuilder;
    }

    private function getConnectionPool(): ConnectionPool
    {
        return GeneralUtility::makeInstance(ConnectionPool::class);
    }
}
