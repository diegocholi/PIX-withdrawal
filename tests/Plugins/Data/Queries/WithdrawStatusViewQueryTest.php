<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Queries;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\WithdrawStatusViewQuery as DataWithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class WithdrawStatusViewQueryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testFindReturnsOnlyStatusProjectionForAccountScopedWithdraw(): void
    {
        $query = $this->container->get(WithdrawStatusViewQuery::class);
        $createdAt = new DateTimeImmutable('2026-03-29 13:00:00+00:00');
        $this->insertAccount('acc-status-100');

        $withdraw = AccountWithdraw::createScheduled(
            id: 'wd-status-100',
            accountId: 'acc-status-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('45.25'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable('2026-03-30T08:30:00+00:00'),
                $this->clockAt('2026-03-29T08:00:00+00:00'),
            ),
            correlationId: 'corr-status-100',
            idempotencyKey: 'idem-status-100',
            createdAt: $createdAt,
        );
        $withdraw->markAsQueued(new DateTimeImmutable('2026-03-30T08:30:00+00:00'));
        $withdraw->markAsProcessing(new DateTimeImmutable('2026-03-30T08:31:00+00:00'));
        $withdraw->markAsFailedInsufficientFunds(
            new DateTimeImmutable('2026-03-30T08:32:00+00:00'),
            'insufficient_balance',
        );

        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: 'wd-status-100',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'status@example.com'),
            createdAt: $createdAt,
        );

        $this->persistWithdraw($withdraw);
        $this->persistWithdrawPix($withdrawPix);

        $result = $query->find('acc-status-100', 'wd-status-100');

        self::assertInstanceOf(DataWithdrawStatusViewQuery::class, $query);
        self::assertNotNull($result);
        self::assertSame('wd-status-100', $result->withdrawId());
        self::assertSame('FAILED_INSUFFICIENT_FUNDS', $result->status());
        self::assertSame('45.25', $result->amount());
        self::assertSame('PIX', $result->method());
        self::assertTrue($result->scheduled());
        self::assertSame('2026-03-30T08:30:00-03:00', $result->scheduledFor());
        self::assertSame('2026-03-30T08:32:00-03:00', $result->processedAt());
        self::assertSame('insufficient_balance', $result->errorReason());
        self::assertSame('EMAIL', $result->pixKeyType());
        self::assertSame('status@example.com', $result->pixKeyValue());
    }

    public function testFindReturnsNullWhenWithdrawBelongsToAnotherAccount(): void
    {
        $query = $this->container->get(WithdrawStatusViewQuery::class);
        $this->insertAccount('acc-status-owner');
        $this->insertAccount('acc-status-other');
        $this->persistWithdraw(
            AccountWithdraw::createPending(
                id: 'wd-status-200',
                accountId: 'acc-status-owner',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('30.00'),
                correlationId: 'corr-status-200',
                idempotencyKey: 'idem-status-200',
                createdAt: new DateTimeImmutable('2026-03-29 14:00:00+00:00'),
            ),
        );

        self::assertNull($query->find('acc-status-other', 'wd-status-200'));
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Status Query Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-29 12:00:00.000000',
            'updated_at' => '2026-03-29 12:00:00.000000',
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

    private function persistWithdrawPix(AccountWithdrawPix $withdrawPix): void
    {
        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => $withdrawPix->withdrawId(),
            'type' => $withdrawPix->pixKeyType()->value,
            'key' => $withdrawPix->pixKey()->value(),
            'created_at' => $withdrawPix->createdAt()->format('Y-m-d H:i:s.u'),
            'updated_at' => $withdrawPix->updatedAt()->format('Y-m-d H:i:s.u'),
        ]);
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
