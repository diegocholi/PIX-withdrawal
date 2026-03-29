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
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\ProcessableWithdrawQuery as DataProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class ProcessableWithdrawQueryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testLockByIdReturnsWithdrawAndPixPayloadForWorker(): void
    {
        $query = $this->container->get(ProcessableWithdrawQuery::class);
        $createdAt = new DateTimeImmutable('2026-03-29 12:00:00+00:00');
        $this->insertAccount('acc-worker-100');

        $withdraw = AccountWithdraw::createScheduled(
            id: 'wd-worker-100',
            accountId: 'acc-worker-100',
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('88.50'),
            scheduledFor: ScheduleAt::fromDateTime(
                new DateTimeImmutable('2026-03-30T09:00:00+00:00'),
                $this->clockAt('2026-03-29T08:00:00+00:00'),
            ),
            correlationId: 'corr-worker-100',
            idempotencyKey: 'idem-worker-100',
            createdAt: $createdAt,
        );
        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: 'wd-worker-100',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'worker@example.com'),
            createdAt: $createdAt,
        );

        $this->persistWithdraw($withdraw);
        $this->persistWithdrawPix($withdrawPix);

        $result = $query->lockById('wd-worker-100');

        self::assertInstanceOf(DataProcessableWithdrawQuery::class, $query);
        self::assertNotNull($result);
        self::assertSame('wd-worker-100', $result->withdraw()->id());
        self::assertSame('acc-worker-100', $result->withdraw()->accountId());
        self::assertSame('88.50', $result->withdraw()->amount()->toDecimal());
        self::assertSame('EMAIL', $result->withdrawPix()?->pixKeyType()->value);
        self::assertSame('worker@example.com', $result->withdrawPix()?->pixKey()->value());
    }

    public function testLockByIdReturnsNullWhenWithdrawDoesNotExist(): void
    {
        $query = $this->container->get(ProcessableWithdrawQuery::class);

        self::assertNull($query->lockById('wd-worker-missing'));
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Worker Query Account',
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
