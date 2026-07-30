<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Counts how often a questionnaire is started and completed.
 *
 * Two numbers on the questionnaire record instead of a row per run: a row with a
 * moment in it could be crossed with the webserver access logs, which do hold an IP
 * address, while two totals cannot be traced back to anyone. What that costs is the
 * trend over time — there are only totals since counting began.
 */
class StatisticsService
{
    private const TABLE = 'tx_pnquestionnaire_questionnaire';

    public function __construct(private readonly ConnectionPool $connectionPool) {}

    public function countStart(int $questionnaireUid): void
    {
        $this->increment('starts', $questionnaireUid);
    }

    public function countCompletion(int $questionnaireUid): void
    {
        $this->increment('completions', $questionnaireUid);
    }

    /**
     * Raised by the database itself, so two visitors finishing at the same moment cannot
     * overwrite each other's count. tstamp is deliberately left alone: filling in a
     * questionnaire is not editing the record, and touching it would make the backend
     * report the record as changed.
     */
    private function increment(string $column, int $questionnaireUid): void
    {
        if ($questionnaireUid <= 0) {
            return;
        }

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder
            ->update(self::TABLE)
            ->set($column, $queryBuilder->quoteIdentifier($column) . ' + 1', false)
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($questionnaireUid, Connection::PARAM_INT)
                )
            )
            ->executeStatement();
    }
}
