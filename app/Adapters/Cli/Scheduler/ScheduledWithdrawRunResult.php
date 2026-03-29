<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Scheduler;

use DateTimeImmutable;

final readonly class ScheduledWithdrawRunResult
{
    /**
     * @param list<string> $dueWithdrawIds
     * @param list<string> $promotedWithdrawIds
     * @param list<string> $skippedWithdrawIds
     * @param list<string> $publishedWithdrawIds
     * @param list<string> $publicationFailedWithdrawIds
     */
    public function __construct(
        private bool $dryRun,
        private int $batchSize,
        private DateTimeImmutable $scheduledUntil,
        private array $dueWithdrawIds,
        private array $promotedWithdrawIds,
        private array $skippedWithdrawIds,
        private array $publishedWithdrawIds,
        private array $publicationFailedWithdrawIds,
    ) {
    }

    public function dryRun(): bool
    {
        return $this->dryRun;
    }

    public function batchSize(): int
    {
        return $this->batchSize;
    }

    public function scheduledUntil(): DateTimeImmutable
    {
        return $this->scheduledUntil;
    }

    /**
     * @return list<string>
     */
    public function dueWithdrawIds(): array
    {
        return $this->dueWithdrawIds;
    }

    /**
     * @return list<string>
     */
    public function promotedWithdrawIds(): array
    {
        return $this->promotedWithdrawIds;
    }

    /**
     * @return list<string>
     */
    public function skippedWithdrawIds(): array
    {
        return $this->skippedWithdrawIds;
    }

    /**
     * @return list<string>
     */
    public function publishedWithdrawIds(): array
    {
        return $this->publishedWithdrawIds;
    }

    /**
     * @return list<string>
     */
    public function publicationFailedWithdrawIds(): array
    {
        return $this->publicationFailedWithdrawIds;
    }

    public function dueCount(): int
    {
        return count($this->dueWithdrawIds);
    }

    public function promotedCount(): int
    {
        return count($this->promotedWithdrawIds);
    }

    public function skippedCount(): int
    {
        return count($this->skippedWithdrawIds);
    }

    public function publishedCount(): int
    {
        return count($this->publishedWithdrawIds);
    }

    public function publicationFailedCount(): int
    {
        return count($this->publicationFailedWithdrawIds);
    }

    public function hasPartialFailure(): bool
    {
        return $this->skippedCount() > 0 || $this->publicationFailedCount() > 0;
    }
}
