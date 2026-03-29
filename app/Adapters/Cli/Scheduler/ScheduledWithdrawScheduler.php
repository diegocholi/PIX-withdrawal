<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Scheduler;

use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawQueued;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final readonly class ScheduledWithdrawScheduler
{
    public function __construct(
        private DueScheduledWithdrawQuery $dueScheduledWithdrawQuery,
        private ScheduledWithdrawQueuePromotion $scheduledWithdrawQueuePromotion,
        private WithdrawPixRepository $withdrawPixRepository,
        private DomainEventDispatcher $domainEventDispatcher,
        private Clock $clock,
    ) {
    }

    public function run(int $batchSize, bool $dryRun, ?string $executionCorrelationId = null): ScheduledWithdrawRunResult
    {
        $scheduledUntil = $this->clock->now();
        $dueWithdraws = $this->dueScheduledWithdrawQuery->findDue($scheduledUntil, $batchSize);
        $dueWithdrawIds = array_map(
            static fn ($withdraw): string => $withdraw->id(),
            $dueWithdraws,
        );

        if ($dryRun) {
            return new ScheduledWithdrawRunResult(
                dryRun: true,
                batchSize: $batchSize,
                scheduledUntil: $scheduledUntil,
                dueWithdrawIds: $dueWithdrawIds,
                promotedWithdrawIds: [],
                skippedWithdrawIds: [],
                publishedWithdrawIds: [],
                publicationFailedWithdrawIds: [],
            );
        }

        $promotedWithdrawIds = [];
        $skippedWithdrawIds = [];
        $publishedWithdrawIds = [];
        $publicationFailedWithdrawIds = [];

        foreach ($dueWithdraws as $index => $withdraw) {
            $withdrawId = $withdraw->id();
            $promoted = $this->scheduledWithdrawQueuePromotion->promote($withdrawId, $scheduledUntil);

            if (! $promoted) {
                $skippedWithdrawIds[] = $withdrawId;
                continue;
            }

            $promotedWithdrawIds[] = $withdrawId;
            $withdraw->markAsQueued($scheduledUntil);

            try {
                $this->domainEventDispatcher->dispatch(
                    WithdrawQueued::fromAggregate(
                        withdraw: $withdraw,
                        withdrawPix: $this->withdrawPixRepository->findByWithdrawId($withdrawId),
                        occurredAt: $scheduledUntil,
                        traceMetadata: $this->traceMetadata($executionCorrelationId),
                    )
                );

                $publishedWithdrawIds[] = $withdrawId;
            } catch (\Throwable) {
                $publicationFailedWithdrawIds[] = $withdrawId;

                foreach (array_slice($dueWithdraws, $index + 1) as $remainingWithdraw) {
                    $skippedWithdrawIds[] = $remainingWithdraw->id();
                }

                break;
            }
        }

        return new ScheduledWithdrawRunResult(
            dryRun: false,
            batchSize: $batchSize,
            scheduledUntil: $scheduledUntil,
            dueWithdrawIds: $dueWithdrawIds,
            promotedWithdrawIds: $promotedWithdrawIds,
            skippedWithdrawIds: $skippedWithdrawIds,
            publishedWithdrawIds: $publishedWithdrawIds,
            publicationFailedWithdrawIds: $publicationFailedWithdrawIds,
        );
    }

    /**
     * @return array<string, string>
     */
    private function traceMetadata(?string $executionCorrelationId): array
    {
        if ($executionCorrelationId === null || trim($executionCorrelationId) === '') {
            return [];
        }

        return [
            'origin' => 'cli.withdraw_scheduler',
            'cli_correlation_id' => trim($executionCorrelationId),
        ];
    }
}
