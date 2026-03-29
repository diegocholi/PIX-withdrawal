<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Transactions;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\ScheduledWithdrawQueuePromotion as DataScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class ScheduledWithdrawQueuePromotionTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testPromoteQueuesDueScheduledWithdrawSafely(): void
    {
        $promotion = $this->container->get(ScheduledWithdrawQueuePromotion::class);
        $this->insertAccount('acc-queue-100');
        $this->persistWithdraw($this->scheduledWithdraw('wd-queue-100', 'acc-queue-100', '2026-03-29T08:00:00+00:00'));

        $promoted = $promotion->promote('wd-queue-100', new DateTimeImmutable('2026-03-29T09:00:00+00:00'));
        $record = Db::table('account_withdraw')->where('id', 'wd-queue-100')->first();

        self::assertInstanceOf(DataScheduledWithdrawQueuePromotion::class, $promotion);
        self::assertTrue($promoted);
        self::assertSame('QUEUED', $record->status ?? null);
        self::assertSame('2026-03-29 09:00:00.000000', $record->queued_at ?? null);
        self::assertSame('2026-03-29 09:00:00.000000', $record->updated_at ?? null);
    }

    public function testPromoteRejectsFutureScheduledWithdraw(): void
    {
        $promotion = $this->container->get(ScheduledWithdrawQueuePromotion::class);
        $this->insertAccount('acc-queue-200');
        $this->persistWithdraw($this->scheduledWithdraw('wd-queue-200', 'acc-queue-200', '2026-03-29T10:00:00+00:00'));

        $promoted = $promotion->promote('wd-queue-200', new DateTimeImmutable('2026-03-29T09:00:00+00:00'));
        $record = Db::table('account_withdraw')->where('id', 'wd-queue-200')->first();

        self::assertFalse($promoted);
        self::assertSame('SCHEDULED', $record->status ?? null);
        self::assertNull($record->queued_at ?? null);
    }

    public function testPromoteRunsOnlyOnceForTheSameWithdraw(): void
    {
        $promotion = $this->container->get(ScheduledWithdrawQueuePromotion::class);
        $this->insertAccount('acc-queue-300');
        $this->persistWithdraw($this->scheduledWithdraw('wd-queue-300', 'acc-queue-300', '2026-03-29T08:00:00+00:00'));

        $firstPromotion = $promotion->promote('wd-queue-300', new DateTimeImmutable('2026-03-29T09:00:00+00:00'));
        $secondPromotion = $promotion->promote('wd-queue-300', new DateTimeImmutable('2026-03-29T09:05:00+00:00'));
        $record = Db::table('account_withdraw')->where('id', 'wd-queue-300')->first();

        self::assertTrue($firstPromotion);
        self::assertFalse($secondPromotion);
        self::assertSame('QUEUED', $record->status ?? null);
        self::assertSame('2026-03-29 09:00:00.000000', $record->queued_at ?? null);
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Queue Promotion Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }

    private function persistWithdraw(AccountWithdraw $withdraw): void
    {
        Db::table('account_withdraw')->insert([
            'id' => $withdraw->id(),
            'account_id' => $withdraw->accountId(),
            'method' => $withdraw->method()->value,
            'amount' => $withdraw->amount()->toDecimal(),
            'scheduled' => $withdraw->isScheduled() ? 1 : 0,
            'scheduled_for' => $withdraw->scheduledFor()?->value()->format('Y-m-d H:i:s.u'),
            'status' => $withdraw->status()->value,
            'error_reason' => $withdraw->errorReason(),
            'requested_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'queued_at' => $withdraw->queuedAt()?->format('Y-m-d H:i:s.u'),
            'processing_started_at' => $withdraw->processingStartedAt()?->format('Y-m-d H:i:s.u'),
            'processed_at' => $withdraw->processedAt()?->format('Y-m-d H:i:s.u'),
            'correlation_id' => $withdraw->correlationId(),
            'idempotency_key' => $withdraw->idempotencyKey(),
            'retry_count' => $withdraw->retryCount(),
            'last_retry_at' => $withdraw->lastRetryAt()?->format('Y-m-d H:i:s.u'),
            'created_at' => $withdraw->createdAt()->format('Y-m-d H:i:s.u'),
            'updated_at' => $withdraw->updatedAt()->format('Y-m-d H:i:s.u'),
        ]);
    }

    private function scheduledWithdraw(string $withdrawId, string $accountId, string $scheduledFor): AccountWithdraw
    {
        return AccountWithdraw::createScheduled(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('80.00'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable($scheduledFor),
                $this->clockAt('2026-03-28T08:00:00+00:00'),
            ),
            correlationId: sprintf('corr-%s', $withdrawId),
            idempotencyKey: sprintf('idem-%s', $withdrawId),
            createdAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
        );
    }

    private function clockAt(string $dateTime): \Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock
    {
        return new class ($dateTime) implements \Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock {
            public function __construct(private string $dateTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable($this->dateTime);
            }
        };
    }
}
