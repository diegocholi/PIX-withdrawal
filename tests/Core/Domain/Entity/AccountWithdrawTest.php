<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Entity;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStatusTransition;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class AccountWithdrawTest extends TestCase
{
    public function testAccountWithdrawCanBeCreatedForImmediateProcessing(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');

        $withdraw = AccountWithdraw::createPending(
            id: ' wd-1 ',
            accountId: ' acc-1 ',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: ' corr-1 ',
            idempotencyKey: ' idem-1 ',
            createdAt: $createdAt,
        );

        self::assertSame('wd-1', $withdraw->id());
        self::assertSame('acc-1', $withdraw->accountId());
        self::assertSame(WithdrawMethod::PIX, $withdraw->method());
        self::assertTrue($withdraw->amount()->equals(Money::fromDecimal('100.00')));
        self::assertSame(WithdrawStatus::PENDING, $withdraw->status());
        self::assertSame('corr-1', $withdraw->correlationId());
        self::assertSame('idem-1', $withdraw->idempotencyKey());
        self::assertSame(0, $withdraw->retryCount());
        self::assertTrue($withdraw->isImmediate());
        self::assertFalse($withdraw->isScheduled());
        self::assertFalse($withdraw->isFinal());
    }

    public function testAccountWithdrawCanBeCreatedForScheduledProcessing(): void
    {
        $createdAt = new DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $scheduleAt = ScheduleAt::fromString('2026-03-29T10:00:00-03:00', $this->clockAt('2026-03-28T10:00:00-03:00'));

        $withdraw = AccountWithdraw::createScheduled(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            scheduledFor: $scheduleAt,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: $createdAt,
        );

        self::assertSame(WithdrawStatus::SCHEDULED, $withdraw->status());
        self::assertTrue($withdraw->isScheduled());
        self::assertSame($scheduleAt, $withdraw->scheduledFor());
    }

    public function testAccountWithdrawCanBeReconstitutedInProcessingState(): void
    {
        $withdraw = AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::PROCESSING,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 2,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:06:00-03:00'),
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            processingStartedAt: new DateTimeImmutable('2026-03-28T10:06:00-03:00'),
            processedAt: null,
            errorReason: null,
            lastRetryAt: new DateTimeImmutable('2026-03-28T10:04:00-03:00'),
        );

        self::assertTrue($withdraw->isProcessing());
        self::assertFalse($withdraw->isQueued());
        self::assertFalse($withdraw->isFinal());
        self::assertSame(2, $withdraw->retryCount());
        self::assertSame('2026-03-28T10:04:00-03:00', $withdraw->lastRetryAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:05:00-03:00', $withdraw->queuedAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:06:00-03:00', $withdraw->processingStartedAt()?->format(DATE_ATOM));
    }

    public function testAccountWithdrawRejectsEmptyCorrelationId(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw correlation_id cannot be empty.');

        AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: '   ',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testAccountWithdrawRejectsNegativeRetryCount(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw retry_count cannot be negative.');

        AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::PENDING,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: -1,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            errorReason: null,
        );
    }

    public function testAccountWithdrawRejectsScheduledStatusWithoutScheduledFor(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw state is inconsistent for status "SCHEDULED".');

        AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::SCHEDULED,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            errorReason: null,
        );
    }

    public function testAccountWithdrawRejectsProcessingStatusWithoutProcessingTimestamp(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw state is inconsistent for status "PROCESSING".');

        AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::PROCESSING,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            processingStartedAt: null,
            processedAt: null,
            errorReason: null,
        );
    }

    public function testAccountWithdrawRejectsFinalStatusWithoutProcessedAt(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw state is inconsistent for status "DONE".');

        AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::DONE,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:06:00-03:00'),
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            processingStartedAt: new DateTimeImmutable('2026-03-28T10:06:00-03:00'),
            processedAt: null,
            errorReason: null,
        );
    }

    public function testAccountWithdrawCanTransitionFromPendingToScheduled(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
        $scheduleAt = ScheduleAt::fromString('2026-03-29T10:00:00-03:00', $this->clockAt('2026-03-28T10:00:00-03:00'));

        $withdraw->markAsScheduled($scheduleAt, new DateTimeImmutable('2026-03-28T10:01:00-03:00'));

        self::assertSame(WithdrawStatus::SCHEDULED, $withdraw->status());
        self::assertSame($scheduleAt, $withdraw->scheduledFor());
        self::assertSame('2026-03-28T10:01:00-03:00', $withdraw->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountWithdrawCanTransitionToQueuedFromPendingOrScheduled(): void
    {
        $pending = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $pending->markAsQueued(new DateTimeImmutable('2026-03-28T10:02:00-03:00'));

        self::assertSame(WithdrawStatus::QUEUED, $pending->status());
        self::assertSame('2026-03-28T10:02:00-03:00', $pending->queuedAt()?->format(DATE_ATOM));

        $scheduled = AccountWithdraw::createScheduled(
            id: 'wd-2',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            scheduledFor: ScheduleAt::fromString('2026-03-29T10:00:00-03:00', $this->clockAt('2026-03-28T10:00:00-03:00')),
            correlationId: 'corr-2',
            idempotencyKey: 'idem-2',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $scheduled->markAsQueued(new DateTimeImmutable('2026-03-29T10:00:00-03:00'));

        self::assertSame(WithdrawStatus::QUEUED, $scheduled->status());
    }

    public function testAccountWithdrawCanTransitionFromQueuedToProcessing(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T10:02:00-03:00'));

        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T10:03:00-03:00'));

        self::assertSame(WithdrawStatus::PROCESSING, $withdraw->status());
        self::assertSame('2026-03-28T10:03:00-03:00', $withdraw->processingStartedAt()?->format(DATE_ATOM));
    }

    public function testAccountWithdrawCanTransitionFromProcessingToFinalStates(): void
    {
        $done = $this->processingWithdraw('wd-1');
        $done->markAsDone(new DateTimeImmutable('2026-03-28T10:04:00-03:00'));

        self::assertSame(WithdrawStatus::DONE, $done->status());
        self::assertTrue($done->isDone());
        self::assertFalse($done->hasError());
        self::assertNull($done->errorReason());
        self::assertSame('2026-03-28T10:04:00-03:00', $done->processedAt()?->format(DATE_ATOM));

        $insufficient = $this->processingWithdraw('wd-2');
        $insufficient->markAsFailedInsufficientFunds(new DateTimeImmutable('2026-03-28T10:04:00-03:00'), 'insufficient_balance');

        self::assertSame(WithdrawStatus::FAILED_INSUFFICIENT_FUNDS, $insufficient->status());
        self::assertFalse($insufficient->isDone());
        self::assertTrue($insufficient->hasError());
        self::assertSame('insufficient_balance', $insufficient->errorReason());

        $internal = $this->processingWithdraw('wd-3');
        $internal->markAsFailedInternal(new DateTimeImmutable('2026-03-28T10:04:00-03:00'), 'provider_unavailable');

        self::assertSame(WithdrawStatus::FAILED_INTERNAL, $internal->status());
        self::assertFalse($internal->isDone());
        self::assertTrue($internal->hasError());
        self::assertSame('provider_unavailable', $internal->errorReason());
    }

    public function testAccountWithdrawCanRegisterRetryMetadataForTransientFailure(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T10:02:00-03:00'));

        $withdraw->registerRetry(new DateTimeImmutable('2026-03-28T10:03:00-03:00'));

        self::assertSame(1, $withdraw->retryCount());
        self::assertSame('2026-03-28T10:03:00-03:00', $withdraw->lastRetryAt()?->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:03:00-03:00', $withdraw->updatedAt()->format(DATE_ATOM));
    }

    public function testAccountWithdrawRejectsRetryRegistrationForFinalStatus(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);

        $withdraw = $this->processingWithdraw('wd-1');
        $withdraw->markAsDone(new DateTimeImmutable('2026-03-28T10:04:00-03:00'));

        $withdraw->registerRetry(new DateTimeImmutable('2026-03-28T10:05:00-03:00'));
    }

    public function testAccountWithdrawRejectsRetryMetadataWithoutRetryCount(): void
    {
        $this->expectException(InvalidAccountWithdraw::class);

        AccountWithdraw::reconstitute(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::QUEUED,
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:02:00-03:00'),
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-28T10:02:00-03:00'),
            processingStartedAt: null,
            processedAt: null,
            errorReason: null,
            lastRetryAt: new DateTimeImmutable('2026-03-28T10:03:00-03:00'),
        );
    }

    public function testAccountWithdrawDerivesDoneAndErrorFlagsFromCurrentStatus(): void
    {
        $pending = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        self::assertFalse($pending->isDone());
        self::assertFalse($pending->hasError());

        $validationFailure = AccountWithdraw::reconstitute(
            id: 'wd-2',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            status: WithdrawStatus::FAILED_VALIDATION,
            correlationId: 'corr-2',
            idempotencyKey: 'idem-2',
            retryCount: 0,
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            updatedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            scheduledFor: null,
            queuedAt: new DateTimeImmutable('2026-03-28T10:02:00-03:00'),
            processingStartedAt: new DateTimeImmutable('2026-03-28T10:03:00-03:00'),
            processedAt: new DateTimeImmutable('2026-03-28T10:05:00-03:00'),
            errorReason: 'validation_failed',
        );

        self::assertFalse($validationFailure->isDone());
        self::assertTrue($validationFailure->hasError());
    }

    public function testAccountWithdrawRejectsInvalidStatusTransition(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $this->expectException(InvalidWithdrawStatusTransition::class);
        $this->expectExceptionMessage('Withdraw status transition from "PENDING" to "PROCESSING" is invalid.');

        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T10:01:00-03:00'));
    }

    public function testAccountWithdrawRejectsTransitionTimestampThatMovesBackwards(): void
    {
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-1',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );

        $this->expectException(InvalidAccountWithdraw::class);
        $this->expectExceptionMessage('Account withdraw state is inconsistent for status "PENDING".');

        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T09:59:59-03:00'));
    }

    private function processingWithdraw(string $withdrawId): AccountWithdraw
    {
        $withdraw = AccountWithdraw::createPending(
            id: $withdrawId,
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('100.00'),
            correlationId: 'corr-1',
            idempotencyKey: 'idem-1',
            createdAt: new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-28T10:02:00-03:00'));
        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-28T10:03:00-03:00'));

        return $withdraw;
    }

    private function clockAt(string $now): Clock
    {
        $currentTime = new DateTimeImmutable($now);

        return new class ($currentTime) implements Clock {
            public function __construct(private readonly DateTimeImmutable $currentTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                return $this->currentTime;
            }
        };
    }
}
