<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Queries;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\DueScheduledWithdrawQuery as DataDueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class DueScheduledWithdrawQueryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testFindDueReturnsOnlyScheduledWithdrawsAlreadyExpired(): void
    {
        $query = $this->container->get(DueScheduledWithdrawQuery::class);
        $this->insertAccount('acc-due-100');
        $this->insertAccount('acc-due-101');

        $this->persistWithdraw($this->scheduledWithdraw('wd-due-100', 'acc-due-100', '2026-03-29T08:00:00+00:00'));
        $this->persistWithdraw($this->scheduledWithdraw('wd-due-101', 'acc-due-101', '2026-03-29T10:30:00+00:00'));
        $this->persistWithdraw($this->pendingWithdraw('wd-due-pending', 'acc-due-100'));

        $result = $query->findDue(new DateTimeImmutable('2026-03-29T09:00:00+00:00'), 10);

        self::assertInstanceOf(DataDueScheduledWithdrawQuery::class, $query);
        self::assertCount(1, $result);
        self::assertSame('wd-due-100', $result[0]->id());
        self::assertSame('SCHEDULED', $result[0]->status()->value);
    }

    public function testFindDueAppliesStableBatchLimitAndOrdering(): void
    {
        $query = $this->container->get(DueScheduledWithdrawQuery::class);
        $this->insertAccount('acc-due-200');
        $this->insertAccount('acc-due-201');
        $this->insertAccount('acc-due-202');

        $this->persistWithdraw($this->scheduledWithdraw('wd-due-200', 'acc-due-200', '2026-03-29T08:30:00+00:00'));
        $this->persistWithdraw($this->scheduledWithdraw('wd-due-201', 'acc-due-201', '2026-03-29T07:30:00+00:00'));
        $this->persistWithdraw($this->scheduledWithdraw('wd-due-202', 'acc-due-202', '2026-03-29T08:00:00+00:00'));

        $result = $query->findDue(new DateTimeImmutable('2026-03-29T09:00:00+00:00'), 2);

        self::assertCount(2, $result);
        self::assertSame(['wd-due-201', 'wd-due-202'], array_map(
            static fn (AccountWithdraw $withdraw): string => $withdraw->id(),
            $result,
        ));
    }

    public function testFindDueReturnsEmptyListWhenLimitIsNotPositive(): void
    {
        $query = $this->container->get(DueScheduledWithdrawQuery::class);

        self::assertSame([], $query->findDue(new DateTimeImmutable('2026-03-29T09:00:00+00:00'), 0));
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Due Query Test Account',
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
            amount: Money::fromDecimal('50.00'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable($scheduledFor),
                $this->clockAt('2026-03-28T08:00:00+00:00'),
            ),
            correlationId: sprintf('corr-%s', $withdrawId),
            idempotencyKey: sprintf('idem-%s', $withdrawId),
            createdAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
        );
    }

    private function pendingWithdraw(string $withdrawId, string $accountId): AccountWithdraw
    {
        return AccountWithdraw::createPending(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('50.00'),
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
