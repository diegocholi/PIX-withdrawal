<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Entity;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStatusTransition;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;

final class AccountWithdraw
{
    private function __construct(
        private string $id,
        private string $accountId,
        private WithdrawMethod $method,
        private Money $amount,
        private WithdrawStatus $status,
        private string $correlationId,
        private string $idempotencyKey,
        private string $duplicateGuardFingerprint,
        private int $retryCount,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
        private ?ScheduleAt $scheduledFor = null,
        private ?DateTimeImmutable $queuedAt = null,
        private ?DateTimeImmutable $processingStartedAt = null,
        private ?DateTimeImmutable $processedAt = null,
        private ?string $errorReason = null,
        private ?DateTimeImmutable $lastRetryAt = null,
    ) {
        $this->id = trim($this->id);
        $this->accountId = trim($this->accountId);
        $this->correlationId = trim($this->correlationId);
        $this->idempotencyKey = trim($this->idempotencyKey);
        $this->duplicateGuardFingerprint = trim($this->duplicateGuardFingerprint);
        $this->errorReason = $this->errorReason !== null ? trim($this->errorReason) : null;

        $this->guardState();
    }

    public static function createPending(
        string $id,
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        string $correlationId,
        string $idempotencyKey,
        DateTimeImmutable $createdAt,
        ?string $duplicateGuardFingerprint = null,
    ): self {
        return new self(
            id: $id,
            accountId: $accountId,
            method: $method,
            amount: $amount,
            status: WithdrawStatus::PENDING,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            duplicateGuardFingerprint: $duplicateGuardFingerprint ?? $idempotencyKey,
            retryCount: 0,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        );
    }

    public static function createQueued(
        string $id,
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        string $correlationId,
        string $idempotencyKey,
        DateTimeImmutable $queuedAt,
        ?string $duplicateGuardFingerprint = null,
    ): self {
        return new self(
            id: $id,
            accountId: $accountId,
            method: $method,
            amount: $amount,
            status: WithdrawStatus::QUEUED,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            duplicateGuardFingerprint: $duplicateGuardFingerprint ?? $idempotencyKey,
            retryCount: 0,
            createdAt: $queuedAt,
            updatedAt: $queuedAt,
            queuedAt: $queuedAt,
        );
    }

    public static function createScheduled(
        string $id,
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        ScheduleAt $scheduledFor,
        string $correlationId,
        string $idempotencyKey,
        DateTimeImmutable $createdAt,
        ?string $duplicateGuardFingerprint = null,
    ): self {
        return new self(
            id: $id,
            accountId: $accountId,
            method: $method,
            amount: $amount,
            status: WithdrawStatus::SCHEDULED,
            correlationId: $correlationId,
            idempotencyKey: $idempotencyKey,
            duplicateGuardFingerprint: $duplicateGuardFingerprint ?? $idempotencyKey,
            retryCount: 0,
            createdAt: $createdAt,
            updatedAt: $createdAt,
            scheduledFor: $scheduledFor,
        );
    }

    public static function reconstitute(
        string $id,
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        WithdrawStatus $status,
        string $correlationId,
        string $idempotencyKey,
        int $retryCount,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
        ?ScheduleAt $scheduledFor = null,
        ?DateTimeImmutable $queuedAt = null,
        ?DateTimeImmutable $processingStartedAt = null,
        ?DateTimeImmutable $processedAt = null,
        ?string $errorReason = null,
        ?DateTimeImmutable $lastRetryAt = null,
        ?string $duplicateGuardFingerprint = null,
    ): self {
        return new self(
            $id,
            $accountId,
            $method,
            $amount,
            $status,
            $correlationId,
            $idempotencyKey,
            $duplicateGuardFingerprint ?? $idempotencyKey,
            $retryCount,
            $createdAt,
            $updatedAt,
            $scheduledFor,
            $queuedAt,
            $processingStartedAt,
            $processedAt,
            $errorReason,
            $lastRetryAt,
        );
    }

    public function id(): string
    {
        return $this->id;
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function method(): WithdrawMethod
    {
        return $this->method;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function status(): WithdrawStatus
    {
        return $this->status;
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }

    public function retryCount(): int
    {
        return $this->retryCount;
    }

    public function duplicateGuardFingerprint(): string
    {
        return $this->duplicateGuardFingerprint;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function scheduledFor(): ?ScheduleAt
    {
        return $this->scheduledFor;
    }

    public function queuedAt(): ?DateTimeImmutable
    {
        return $this->queuedAt;
    }

    public function processingStartedAt(): ?DateTimeImmutable
    {
        return $this->processingStartedAt;
    }

    public function processedAt(): ?DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function errorReason(): ?string
    {
        return $this->errorReason;
    }

    public function lastRetryAt(): ?DateTimeImmutable
    {
        return $this->lastRetryAt;
    }

    public function isScheduled(): bool
    {
        return $this->scheduledFor !== null;
    }

    public function isImmediate(): bool
    {
        return ! $this->isScheduled();
    }

    public function isQueued(): bool
    {
        return $this->status === WithdrawStatus::QUEUED;
    }

    public function isProcessing(): bool
    {
        return $this->status === WithdrawStatus::PROCESSING;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isDone(): bool
    {
        return $this->status === WithdrawStatus::DONE;
    }

    public function hasError(): bool
    {
        return in_array(
            $this->status,
            [
                WithdrawStatus::FAILED_INSUFFICIENT_FUNDS,
                WithdrawStatus::FAILED_VALIDATION,
                WithdrawStatus::FAILED_INTERNAL,
            ],
            true
        );
    }

    public function markAsScheduled(ScheduleAt $scheduledFor, DateTimeImmutable $updatedAt): void
    {
        $this->assertTransitionTo(WithdrawStatus::SCHEDULED, [WithdrawStatus::PENDING]);
        $this->assertProgressiveTimestamp($updatedAt);

        $this->status = WithdrawStatus::SCHEDULED;
        $this->scheduledFor = $scheduledFor;
        $this->updatedAt = $updatedAt;

        $this->guardState();
    }

    public function markAsQueued(DateTimeImmutable $queuedAt): void
    {
        $this->assertTransitionTo(WithdrawStatus::QUEUED, [WithdrawStatus::PENDING, WithdrawStatus::SCHEDULED]);
        $this->assertProgressiveTimestamp($queuedAt);

        $this->status = WithdrawStatus::QUEUED;
        $this->queuedAt = $queuedAt;
        $this->updatedAt = $queuedAt;

        $this->guardState();
    }

    public function markAsProcessing(DateTimeImmutable $processingStartedAt): void
    {
        $this->assertTransitionTo(WithdrawStatus::PROCESSING, [WithdrawStatus::QUEUED]);
        $this->assertProgressiveTimestamp($processingStartedAt);

        $this->status = WithdrawStatus::PROCESSING;
        $this->queuedAt ??= $processingStartedAt;
        $this->processingStartedAt = $processingStartedAt;
        $this->updatedAt = $processingStartedAt;

        $this->guardState();
    }

    public function markAsDone(DateTimeImmutable $processedAt): void
    {
        $this->assertTransitionTo(WithdrawStatus::DONE, [WithdrawStatus::PROCESSING]);
        $this->markAsFinal(WithdrawStatus::DONE, $processedAt);
    }

    public function markAsFailedInsufficientFunds(DateTimeImmutable $processedAt, string $errorReason): void
    {
        $this->assertTransitionTo(WithdrawStatus::FAILED_INSUFFICIENT_FUNDS, [WithdrawStatus::PROCESSING]);
        $this->markAsFinal(WithdrawStatus::FAILED_INSUFFICIENT_FUNDS, $processedAt, $errorReason);
    }

    public function markAsFailedInternal(DateTimeImmutable $processedAt, string $errorReason): void
    {
        $this->assertTransitionTo(WithdrawStatus::FAILED_INTERNAL, [WithdrawStatus::PROCESSING]);
        $this->markAsFinal(WithdrawStatus::FAILED_INTERNAL, $processedAt, $errorReason);
    }

    public function registerRetry(DateTimeImmutable $retriedAt): void
    {
        if ($this->isFinal()) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'final_status_cannot_register_retry',
            );
        }

        $this->assertProgressiveTimestamp($retriedAt);

        $this->retryCount++;
        $this->lastRetryAt = $retriedAt;
        $this->updatedAt = $retriedAt;

        $this->guardState();
    }

    private function guardState(): void
    {
        foreach (
            [
                'id' => $this->id,
                'account_id' => $this->accountId,
                'correlation_id' => $this->correlationId,
                'idempotency_key' => $this->idempotencyKey,
                'duplicate_guard_fingerprint' => $this->duplicateGuardFingerprint,
            ] as $field => $value
        ) {
            if ($value === '') {
                throw InvalidAccountWithdraw::emptyField($field);
            }
        }

        if ($this->retryCount < 0) {
            throw InvalidAccountWithdraw::negativeRetryCount($this->retryCount);
        }

        if ($this->retryCount === 0 && $this->lastRetryAt !== null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'retry_metadata_requires_retry_count',
            );
        }

        if ($this->retryCount > 0 && $this->lastRetryAt === null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'retry_count_requires_last_retry_at',
            );
        }

        if ($this->updatedAt < $this->createdAt) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'updated_at_before_created_at',
            );
        }

        if ($this->lastRetryAt !== null && $this->lastRetryAt < $this->createdAt) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'last_retry_at_before_created_at',
            );
        }

        if ($this->status === WithdrawStatus::SCHEDULED && $this->scheduledFor === null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'scheduled_status_requires_scheduled_for',
            );
        }

        if ($this->status !== WithdrawStatus::SCHEDULED && $this->scheduledFor !== null && $this->status === WithdrawStatus::PENDING) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'pending_status_cannot_have_scheduled_for',
            );
        }

        if (in_array($this->status, [WithdrawStatus::QUEUED, WithdrawStatus::PROCESSING], true) && $this->queuedAt === null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'queued_status_requires_queued_at',
            );
        }

        if ($this->status === WithdrawStatus::PROCESSING && $this->processingStartedAt === null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'processing_status_requires_processing_started_at',
            );
        }

        if ($this->status->isFinal() && $this->processedAt === null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'final_status_requires_processed_at',
            );
        }

        if (in_array($this->status, [WithdrawStatus::PENDING, WithdrawStatus::SCHEDULED], true) && $this->queuedAt !== null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'status_cannot_have_queued_at',
            );
        }

        if (in_array($this->status, [WithdrawStatus::PENDING, WithdrawStatus::SCHEDULED, WithdrawStatus::QUEUED], true) && $this->processingStartedAt !== null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'status_cannot_have_processing_started_at',
            );
        }

        if (! $this->status->isFinal() && $this->processedAt !== null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'non_final_status_cannot_have_processed_at',
            );
        }

        if ($this->hasError() && ($this->errorReason === null || $this->errorReason === '')) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'error_status_requires_error_reason',
            );
        }

        if (! $this->hasError() && $this->errorReason !== null) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'non_error_status_cannot_have_error_reason',
            );
        }
    }

    /**
     * @param list<WithdrawStatus> $allowedCurrentStatuses
     */
    private function assertTransitionTo(WithdrawStatus $targetStatus, array $allowedCurrentStatuses): void
    {
        if (! in_array($this->status, $allowedCurrentStatuses, true)) {
            throw InvalidWithdrawStatusTransition::fromTo($this->status, $targetStatus);
        }
    }

    private function assertProgressiveTimestamp(DateTimeImmutable $updatedAt): void
    {
        if ($updatedAt < $this->updatedAt) {
            throw InvalidAccountWithdraw::inconsistentState(
                $this->status,
                'transition_timestamp_cannot_move_backwards',
                [
                    'current_updated_at' => $this->updatedAt->format(DATE_ATOM),
                    'new_updated_at' => $updatedAt->format(DATE_ATOM),
                ]
            );
        }
    }

    private function markAsFinal(WithdrawStatus $status, DateTimeImmutable $processedAt, ?string $errorReason = null): void
    {
        $this->assertProgressiveTimestamp($processedAt);

        $this->status = $status;
        $this->processedAt = $processedAt;
        $this->updatedAt = $processedAt;
        $this->errorReason = $errorReason !== null ? trim($errorReason) : null;

        $this->guardState();
    }
}
