<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Event;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawFailed;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawProcessed;
use Tecnofit\PixWithdrawal\Core\Domain\Event\WithdrawQueued;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final class DomainEventContractTest extends TestCase
{
    public function testDomainEventContractRemainsStable(): void
    {
        $reflection = new ReflectionClass(DomainEvent::class);

        self::assertTrue($reflection->isInterface());
        self::assertTrue($reflection->implementsInterface(SerializableDto::class));
        self::assertTrue($reflection->hasMethod('eventName'));
        self::assertTrue($reflection->hasMethod('aggregateId'));
        self::assertTrue($reflection->hasMethod('occurredAt'));
        self::assertTrue($reflection->hasMethod('correlationId'));
        self::assertTrue($reflection->hasMethod('traceMetadata'));
    }

    public function testGenericDomainEventIsReadonlyAndSerializable(): void
    {
        $event = new GenericDomainEvent(
            'withdraw.queued',
            'wd-1',
            '2026-03-28T10:00:00-03:00',
            'corr-1',
            ['trace_id' => 'trace-1'],
            ['status' => 'QUEUED']
        );

        self::assertTrue((new ReflectionClass($event))->isReadOnly());
        self::assertSame(
            [
                'event_name' => 'withdraw.queued',
                'aggregate_id' => 'wd-1',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
                'correlation_id' => 'corr-1',
                'trace_metadata' => ['trace_id' => 'trace-1'],
                'payload' => ['status' => 'QUEUED'],
            ],
            $event->toArray()
        );
    }

    public function testWithdrawQueuedUsesDedicatedPayloadContract(): void
    {
        $createdAt = new \DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-2',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-2',
            idempotencyKey: 'idem-2',
            createdAt: $createdAt,
        );
        $withdraw->markAsQueued(new \DateTimeImmutable('2026-03-28T10:01:00-03:00'));
        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: 'wd-2',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::email('queue@example.com'),
            createdAt: $createdAt,
        );

        $event = WithdrawQueued::fromAggregate(
            withdraw: $withdraw,
            withdrawPix: $withdrawPix,
            occurredAt: new \DateTimeImmutable('2026-03-28T10:01:00-03:00'),
            traceMetadata: ['trace_id' => 'trace-2'],
        );

        self::assertTrue((new ReflectionClass($event))->isReadOnly());
        self::assertSame('withdraw.queued', $event->eventName());
        self::assertSame(
            [
                'event_name' => 'withdraw.queued',
                'aggregate_id' => 'wd-2',
                'occurred_at' => '2026-03-28T10:01:00-03:00',
                'correlation_id' => 'corr-2',
                'trace_metadata' => ['trace_id' => 'trace-2'],
                'payload' => [
                    'withdraw_id' => 'wd-2',
                    'account_id' => 'acc-1',
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '25.00',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T10:01:00-03:00',
                    'scheduled_at' => null,
                ],
            ],
            $event->toArray()
        );
    }

    public function testWithdrawProcessedUsesDedicatedPayloadContract(): void
    {
        $createdAt = new \DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-3',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-3',
            idempotencyKey: 'idem-3',
            createdAt: $createdAt,
        );
        $withdraw->markAsQueued(new \DateTimeImmutable('2026-03-28T10:01:00-03:00'));
        $withdraw->markAsProcessing(new \DateTimeImmutable('2026-03-28T10:02:00-03:00'));
        $withdraw->markAsDone(new \DateTimeImmutable('2026-03-28T10:03:00-03:00'));

        $event = WithdrawProcessed::fromAggregate(
            withdraw: $withdraw,
            correlationId: 'corr-3',
            occurredAt: new \DateTimeImmutable('2026-03-28T10:03:00-03:00'),
            pixKeyType: 'EMAIL',
            pixKeyMasked: 'u***@example.com',
            traceMetadata: ['trace_id' => 'trace-3'],
        );

        self::assertTrue((new ReflectionClass($event))->isReadOnly());
        self::assertSame('withdraw.processed', $event->eventName());
        self::assertSame(
            [
                'event_name' => 'withdraw.processed',
                'aggregate_id' => 'wd-3',
                'occurred_at' => '2026-03-28T10:03:00-03:00',
                'correlation_id' => 'corr-3',
                'trace_metadata' => ['trace_id' => 'trace-3'],
                'payload' => [
                    'withdraw_id' => 'wd-3',
                    'account_id' => 'acc-1',
                    'amount' => '25.00',
                    'method' => 'PIX',
                    'status' => 'DONE',
                    'pix_key_type' => 'EMAIL',
                    'pix_key_masked' => 'u***@example.com',
                    'error_reason' => null,
                ],
            ],
            $event->toArray()
        );
    }

    public function testWithdrawFailedUsesDedicatedPayloadContract(): void
    {
        $createdAt = new \DateTimeImmutable('2026-03-28T10:00:00-03:00');
        $withdraw = AccountWithdraw::createPending(
            id: 'wd-4',
            accountId: 'acc-1',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('25.00'),
            correlationId: 'corr-4',
            idempotencyKey: 'idem-4',
            createdAt: $createdAt,
        );
        $withdraw->markAsQueued(new \DateTimeImmutable('2026-03-28T10:01:00-03:00'));
        $withdraw->markAsProcessing(new \DateTimeImmutable('2026-03-28T10:02:00-03:00'));
        $withdraw->markAsFailedInsufficientFunds(new \DateTimeImmutable('2026-03-28T10:03:00-03:00'), 'insufficient_balance');

        $event = WithdrawFailed::fromAggregate(
            withdraw: $withdraw,
            correlationId: 'corr-4',
            occurredAt: new \DateTimeImmutable('2026-03-28T10:03:00-03:00'),
            pixKeyType: 'EMAIL',
            pixKeyMasked: 'u***@example.com',
            traceMetadata: ['trace_id' => 'trace-4'],
        );

        self::assertTrue((new ReflectionClass($event))->isReadOnly());
        self::assertSame('withdraw.failed', $event->eventName());
        self::assertSame(
            [
                'event_name' => 'withdraw.failed',
                'aggregate_id' => 'wd-4',
                'occurred_at' => '2026-03-28T10:03:00-03:00',
                'correlation_id' => 'corr-4',
                'trace_metadata' => ['trace_id' => 'trace-4'],
                'payload' => [
                    'withdraw_id' => 'wd-4',
                    'account_id' => 'acc-1',
                    'amount' => '25.00',
                    'method' => 'PIX',
                    'status' => 'FAILED_INSUFFICIENT_FUNDS',
                    'pix_key_type' => 'EMAIL',
                    'pix_key_masked' => 'u***@example.com',
                    'error_reason' => 'insufficient_balance',
                ],
            ],
            $event->toArray()
        );
    }
}
