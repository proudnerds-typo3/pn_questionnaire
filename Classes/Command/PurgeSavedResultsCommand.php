<?php

declare(strict_types=1);

namespace ProudNerds\PnQuestionnaire\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;

/**
 * Removes saved questionnaire results whose retention period has passed. Meant to run
 * daily via the scheduler.
 *
 * The expiry moment sits on the row itself, so this command reads no setting: it cannot
 * disagree with the retention period an editor configured, not even when that period
 * changed after the row was written.
 *
 * Deletes for real instead of setting the deleted flag, and includes rows an editor
 * already discarded — otherwise a discarded result would outlive its retention period in
 * the recycler.
 *
 * Result reporting goes to two channels so the outcome is visible wherever the command
 * runs: the CLI, and the Scheduler backend (flash messages — the scheduler discards
 * command stdout via NullOutput, but renders the default flash message queue). Both
 * channels report which results went, so a retention period can be shown to have been
 * honoured. The token is deliberately left out of the report: it grants access to the
 * result, and a report should not carry a key.
 *
 * Usage:
 *   # Delete every saved result whose retention period has passed — the scheduled default
 *   ddev typo3 pnquestionnaire:purgesavedresults
 *
 *   # Report what would be deleted, change nothing
 *   ddev typo3 pnquestionnaire:purgesavedresults --dry-run
 */
#[AsCommand(
    'pnquestionnaire:purgesavedresults',
    'Permanently deletes saved questionnaire results whose retention period has passed.'
)]
final class PurgeSavedResultsCommand extends Command
{
    private const TABLE = 'tx_pnquestionnaire_saved_result';

    /**
     * How many expired results are listed individually. A purge that clears a backlog of
     * thousands would otherwise turn the report into a wall of text; the rest is
     * summarised as a count, never dropped silently.
     */
    private const MAX_LISTED_RESULTS = 20;

    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly Context $context,
        private readonly FlashMessageService $flashMessageService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Only report how many saved results would be deleted; delete nothing.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $now = (int)$this->context->getPropertyFromAspect('date', 'timestamp', time());
        $dryRun = (bool)$input->getOption('dry-run');

        // Gathered before deleting: afterwards the rows are gone and there is nothing
        // left to report on.
        $total = $this->countAll();
        if ($total === 0) {
            $headline = 'No saved results stored; nothing to purge.';
            $io->note($headline);
            $this->flash($headline, sprintf('Table: %s', self::TABLE), ContextualFeedbackSeverity::INFO);

            return Command::SUCCESS;
        }

        $listed = $this->listExpired($now);
        $expired = $dryRun ? $this->countExpired($now) : $this->deleteExpired($now);

        $this->reportToCli($io, $now, $total, $expired, $listed, $dryRun);
        $this->reportToBackend($now, $total, $expired, $listed, $dryRun);

        return Command::SUCCESS;
    }

    /**
     * @param list<array{uid: int, pid: int, questionnaire: int, expires: int, discarded: bool}> $listed
     */
    private function reportToCli(
        SymfonyStyle $io,
        int $now,
        int $total,
        int $expired,
        array $listed,
        bool $dryRun
    ): void {
        $io->definitionList(
            ['Table' => self::TABLE],
            ['Expired before' => $this->formatMoment($now)],
            ['Mode' => $dryRun ? 'dry run (nothing deleted)' : 'delete'],
            ['Total stored' => (string)$total],
            [($dryRun ? 'Expired' : 'Deleted') => (string)$expired],
            [($dryRun ? 'Would remain' : 'Remaining') => (string)($total - $expired)],
        );

        if ($listed !== []) {
            $io->table(
                ['Uid', 'Folder', 'Questionnaire', 'Expired on', 'Discarded by editor'],
                array_map(
                    fn(array $result): array => [
                        (string)$result['uid'],
                        (string)$result['pid'],
                        (string)$result['questionnaire'],
                        $this->formatMoment($result['expires']),
                        $result['discarded'] ? 'yes' : '—',
                    ],
                    $listed,
                ),
            );
            $omitted = $expired - count($listed);
            if ($omitted > 0) {
                $io->text(sprintf('… and %s not listed above.', $this->plural($omitted, 'result', 'results')));
            }
        }

        $headline = $this->headline($expired, $dryRun);
        if ($dryRun) {
            $io->note($headline);

            return;
        }

        $io->success($headline);
    }

    /**
     * Flash messages, because the scheduler throws away everything written above: its
     * task hands the command a NullOutput and renders the default message queue instead.
     *
     * @param list<array{uid: int, pid: int, questionnaire: int, expires: int, discarded: bool}> $listed
     */
    private function reportToBackend(
        int $now,
        int $total,
        int $expired,
        array $listed,
        bool $dryRun
    ): void {
        $body = [
            sprintf('Table: %s', self::TABLE),
            sprintf('Expired before: %s', $this->formatMoment($now)),
            sprintf(
                'Total stored: %d — %s: %d, %s: %d',
                $total,
                $dryRun ? 'expired' : 'deleted',
                $expired,
                $dryRun ? 'would remain' : 'remaining',
                $total - $expired,
            ),
        ];

        foreach ($listed as $result) {
            $body[] = sprintf(
                '#%d in folder %d (questionnaire %d) expired on %s%s',
                $result['uid'],
                $result['pid'],
                $result['questionnaire'],
                $this->formatMoment($result['expires']),
                $result['discarded'] ? ', already discarded by an editor' : '',
            );
        }
        $omitted = $expired - count($listed);
        if ($omitted > 0) {
            $body[] = sprintf('… and %s not listed individually.', $this->plural($omitted, 'result', 'results'));
        }

        $this->flash(
            $this->headline($expired, $dryRun),
            implode("\n", $body),
            $dryRun ? ContextualFeedbackSeverity::INFO : ContextualFeedbackSeverity::OK,
        );
    }

    private function headline(int $expired, bool $dryRun): string
    {
        if ($dryRun) {
            return sprintf(
                'Dry run: %s would be deleted, nothing was changed.',
                $this->plural($expired, 'expired saved result', 'expired saved results')
            );
        }

        return sprintf('Deleted %s.', $this->plural($expired, 'expired saved result', 'expired saved results'));
    }

    private function countAll(): int
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder->count('uid')->from(self::TABLE);

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }

    private function countExpired(int $now): int
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder
            ->count('uid')
            ->from(self::TABLE)
            ->where(...$this->expiredConstraints($queryBuilder, $now));

        return (int)$queryBuilder->executeQuery()->fetchOne();
    }

    /**
     * The oldest expired results first, capped: enough to report on without loading a
     * whole backlog into memory.
     *
     * @return list<array{uid: int, pid: int, questionnaire: int, expires: int, discarded: bool}>
     */
    private function listExpired(int $now): array
    {
        $queryBuilder = $this->queryBuilder();
        $rows = $queryBuilder
            ->select('uid', 'pid', 'questionnaire', 'expires', 'deleted')
            ->from(self::TABLE)
            ->where(...$this->expiredConstraints($queryBuilder, $now))
            ->orderBy('expires', 'ASC')
            ->setMaxResults(self::MAX_LISTED_RESULTS)
            ->executeQuery()
            ->fetchAllAssociative();

        return array_map(
            static fn(array $row): array => [
                'uid' => (int)$row['uid'],
                'pid' => (int)$row['pid'],
                'questionnaire' => (int)$row['questionnaire'],
                'expires' => (int)$row['expires'],
                'discarded' => (bool)$row['deleted'],
            ],
            $rows,
        );
    }

    private function deleteExpired(int $now): int
    {
        $queryBuilder = $this->queryBuilder();
        $queryBuilder
            ->delete(self::TABLE)
            ->where(...$this->expiredConstraints($queryBuilder, $now));

        return $queryBuilder->executeStatement();
    }

    /**
     * Mirrors SavedResult::isExpired(), so the frontend and this command always agree on
     * which results are expired. A row without an expiry moment is left alone.
     *
     * @return list<string>
     */
    private function expiredConstraints(QueryBuilder $queryBuilder, int $now): array
    {
        return [
            $queryBuilder->expr()->gt('expires', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)),
            $queryBuilder->expr()->lt('expires', $queryBuilder->createNamedParameter($now, Connection::PARAM_INT)),
        ];
    }

    /**
     * Without restrictions, so rows an editor discarded are counted and deleted as well.
     */
    private function queryBuilder(): QueryBuilder
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE);
        $queryBuilder->getRestrictions()->removeAll();

        return $queryBuilder;
    }

    private function flash(string $title, string $body, ContextualFeedbackSeverity $severity): void
    {
        // storeInSession=false: render in the current request only (the scheduler
        // execute request); on CLI it is enqueued but never rendered, which is fine.
        $this->flashMessageService
            ->getMessageQueueByIdentifier()
            ->enqueue(new FlashMessage($body, $title, $severity, false));
    }

    /**
     * Locale-free on purpose: this report is read by administrators and ends up in
     * scheduler output, where an unambiguous moment beats a translated month name.
     */
    private function formatMoment(int $timestamp): string
    {
        return date('Y-m-d H:i', $timestamp);
    }

    private function plural(int $count, string $singular, string $plural): string
    {
        return sprintf('%d %s', $count, $count === 1 ? $singular : $plural);
    }
}
