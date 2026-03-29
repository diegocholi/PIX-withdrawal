<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Repositories;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountWithdrawPixRepository;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlAccountWithdrawPixRepositoryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSaveCreatesAndFindByWithdrawIdReconstitutesPixPayload(): void
    {
        $repository = $this->container->get(WithdrawPixRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');
        $withdrawPix = AccountWithdrawPix::create(
            withdrawId: 'wd-pix-100',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'User@Example.com'),
            createdAt: $createdAt,
        );

        $this->insertWithdraw('wd-pix-100', 'acc-pix-100');
        $repository->save($withdrawPix);

        $persisted = $repository->findByWithdrawId('wd-pix-100');

        self::assertInstanceOf(MySqlAccountWithdrawPixRepository::class, $repository);
        self::assertNotNull($persisted);
        self::assertSame('wd-pix-100', $persisted->withdrawId());
        self::assertSame('PIX', $persisted->method()->value);
        self::assertSame('EMAIL', $persisted->pixKeyType()->value);
        self::assertSame('user@example.com', $persisted->pixKey()->value());
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->createdAt()->format(DATE_ATOM));
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->updatedAt()->format(DATE_ATOM));
    }

    public function testSaveUpdatesExistingPixPayload(): void
    {
        $repository = $this->container->get(WithdrawPixRepository::class);
        $createdAt = new DateTimeImmutable('2026-03-28 13:00:00+00:00');

        $this->insertWithdraw('wd-pix-200', 'acc-pix-200');
        $repository->save(
            AccountWithdrawPix::create(
                withdrawId: 'wd-pix-200',
                method: WithdrawMethod::PIX,
                pixKey: PixKey::from(PixKeyType::EMAIL, 'first@example.com'),
                createdAt: $createdAt,
            )
        );

        $repository->save(
            AccountWithdrawPix::reconstitute(
                withdrawId: 'wd-pix-200',
                method: WithdrawMethod::PIX,
                pixKey: PixKey::from(PixKeyType::EMAIL, 'second@example.com'),
                createdAt: $createdAt,
                updatedAt: new DateTimeImmutable('2026-03-28 13:05:00+00:00'),
            )
        );

        $record = Db::table('account_withdraw_pix')->where('account_withdraw_id', 'wd-pix-200')->first();

        self::assertSame('second@example.com', $record->key ?? null);
        self::assertSame('2026-03-28 13:05:00.000000', $record->updated_at ?? null);
    }

    public function testFindByWithdrawIdReturnsNullWhenPixPayloadDoesNotExist(): void
    {
        $repository = $this->container->get(WithdrawPixRepository::class);

        self::assertNull($repository->findByWithdrawId('missing-pix'));
    }

    private function insertWithdraw(string $withdrawId, string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Repository Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);

        $withdraw = AccountWithdraw::createPending(
            id: $withdrawId,
            accountId: $accountId,
            method: WithdrawMethod::PIX,
            amount: Money::fromDecimal('120.00'),
            correlationId: 'corr-' . $withdrawId,
            idempotencyKey: 'idem-' . $withdrawId,
            createdAt: new DateTimeImmutable('2026-03-28 12:30:00+00:00'),
        );

        Db::table('account_withdraw')->insert([
            'id' => $withdraw->id(),
            'account_id' => $withdraw->accountId(),
            'method' => $withdraw->method()->value,
            'amount' => $withdraw->amount()->toDecimal(),
            'scheduled' => 0,
            'scheduled_for' => null,
            'status' => $withdraw->status()->value,
            'error_reason' => null,
            'requested_at' => '2026-03-28 12:30:00.000000',
            'queued_at' => null,
            'processing_started_at' => null,
            'processed_at' => null,
            'correlation_id' => $withdraw->correlationId(),
            'idempotency_key' => $withdraw->idempotencyKey(),
            'retry_count' => 0,
            'last_retry_at' => null,
            'created_at' => '2026-03-28 12:30:00.000000',
            'updated_at' => '2026-03-28 12:30:00.000000',
        ]);
    }
}
