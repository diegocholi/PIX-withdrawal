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
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\AccountScopedWithdrawQuery as DataAccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class AccountScopedWithdrawQueryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testFindReturnsWithdrawScopedToAccountAndIdentifier(): void
    {
        $query = $this->container->get(AccountScopedWithdrawQuery::class);
        $this->insertAccount('acc-query-100');
        $this->persistWithdraw(
            AccountWithdraw::createPending(
                id: 'wd-query-100',
                accountId: 'acc-query-100',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('55.10'),
                correlationId: 'corr-query-100',
                idempotencyKey: 'idem-query-100',
                createdAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
            ),
        );

        $result = $query->find('acc-query-100', 'wd-query-100');

        self::assertInstanceOf(DataAccountScopedWithdrawQuery::class, $query);
        self::assertNotNull($result);
        self::assertSame('wd-query-100', $result->withdraw()->id());
        self::assertSame('acc-query-100', $result->withdraw()->accountId());
        self::assertSame('55.10', $result->withdraw()->amount()->toDecimal());
        self::assertNull($result->withdrawPix());
    }

    public function testFindReturnsNullWhenWithdrawBelongsToAnotherAccount(): void
    {
        $query = $this->container->get(AccountScopedWithdrawQuery::class);
        $this->insertAccount('acc-query-200');
        $this->insertAccount('acc-query-other');
        $this->persistWithdraw(
            AccountWithdraw::createPending(
                id: 'wd-query-200',
                accountId: 'acc-query-200',
                method: WithdrawMethod::PIX,
                amount: Money::fromDecimal('75.00'),
                correlationId: 'corr-query-200',
                idempotencyKey: 'idem-query-200',
                createdAt: new DateTimeImmutable('2026-03-28 14:00:00+00:00'),
            ),
        );

        self::assertNull($query->find('acc-query-other', 'wd-query-200'));
    }

    public function testFindLoadsAssociatedPixPayloadInSingleRead(): void
    {
        $query = $this->container->get(AccountScopedWithdrawQuery::class);
        $createdAt = new DateTimeImmutable('2026-03-28 15:00:00+00:00');
        $this->insertAccount('acc-query-300');

        $withdraw = AccountWithdraw::createScheduled(
            id: 'wd-query-300',
            accountId: 'acc-query-300',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('99.90'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable('2026-03-29T09:30:00+00:00'),
                $this->clockAt('2026-03-28T08:00:00+00:00'),
            ),
            correlationId: 'corr-query-300',
            idempotencyKey: 'idem-query-300',
            createdAt: $createdAt,
        );
        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: 'wd-query-300',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'query@example.com'),
            createdAt: $createdAt,
        );

        $this->persistWithdraw($withdraw);
        $this->persistWithdrawPix($withdrawPix);

        $result = $query->find('acc-query-300', 'wd-query-300');

        self::assertNotNull($result);
        self::assertTrue($result->withdraw()->isScheduled());
        self::assertSame('2026-03-29T09:30:00+00:00', $result->withdraw()->scheduledFor()?->value()->format(DATE_ATOM));
        self::assertNotNull($result->withdrawPix());
        self::assertSame('EMAIL', $result->withdrawPix()?->pixKeyType()->value);
        self::assertSame('query@example.com', $result->withdrawPix()?->pixKey()->value());
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Query Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }

    private function persistWithdraw(AccountWithdraw $withdraw): void
    {
        $record = [
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
        ];

        Db::table('account_withdraw')->insert($record);
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
