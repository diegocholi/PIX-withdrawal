<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Repositories;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Repositories\MySqlAccountTransactionRepository;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlAccountTransactionRepositoryTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSavePersistsFinancialTrailAndFindByWithdrawIdReconstitutesRecord(): void
    {
        $repository = $this->container->get(AccountTransactionRepository::class);
        $transaction = AccountTransaction::create(
            accountId: 'acc-tx-100',
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: 'wd-tx-100',
            direction: AccountTransactionDirection::DEBIT,
            amount: Money::fromDecimal('25.50'),
            balanceBefore: Money::fromDecimal('100.00'),
            balanceAfter: Money::fromDecimal('74.50'),
            createdAt: new DateTimeImmutable('2026-03-28 13:00:00+00:00'),
        );

        $this->insertAccount('acc-tx-100');
        $repository->save($transaction);

        $persisted = $repository->findByWithdrawId('wd-tx-100');

        self::assertInstanceOf(MySqlAccountTransactionRepository::class, $repository);
        self::assertNotNull($persisted);
        self::assertSame('acc-tx-100', $persisted->accountId());
        self::assertSame('WITHDRAW', $persisted->referenceType()->value);
        self::assertSame('wd-tx-100', $persisted->referenceId());
        self::assertSame('DEBIT', $persisted->direction()->value);
        self::assertSame('25.50', $persisted->amount()->toDecimal());
        self::assertSame('100.00', $persisted->balanceBefore()->toDecimal());
        self::assertSame('74.50', $persisted->balanceAfter()->toDecimal());
        self::assertSame('2026-03-28T13:00:00+00:00', $persisted->createdAt()->format(DATE_ATOM));
    }

    public function testExistsByWithdrawIdReflectsPersistedFinancialTrail(): void
    {
        $repository = $this->container->get(AccountTransactionRepository::class);

        $this->insertAccount('acc-tx-200');
        $repository->save(
            AccountTransaction::create(
                accountId: 'acc-tx-200',
                referenceType: AccountTransactionReferenceType::WITHDRAW,
                referenceId: 'wd-tx-200',
                direction: AccountTransactionDirection::DEBIT,
                amount: Money::fromDecimal('10.00'),
                balanceBefore: Money::fromDecimal('80.00'),
                balanceAfter: Money::fromDecimal('70.00'),
                createdAt: new DateTimeImmutable('2026-03-28 13:10:00+00:00'),
            )
        );

        self::assertTrue($repository->existsByWithdrawId('wd-tx-200'));
        self::assertFalse($repository->existsByWithdrawId('wd-tx-404'));
    }

    public function testSaveCreatesNewRowsForRepeatedFinancialTrailWrites(): void
    {
        $repository = $this->container->get(AccountTransactionRepository::class);

        $this->insertAccount('acc-tx-300');
        $repository->save(
            AccountTransaction::create(
                accountId: 'acc-tx-300',
                referenceType: AccountTransactionReferenceType::WITHDRAW,
                referenceId: 'wd-tx-300',
                direction: AccountTransactionDirection::DEBIT,
                amount: Money::fromDecimal('15.00'),
                balanceBefore: Money::fromDecimal('120.00'),
                balanceAfter: Money::fromDecimal('105.00'),
                createdAt: new DateTimeImmutable('2026-03-28 13:20:00+00:00'),
            )
        );
        $repository->save(
            AccountTransaction::create(
                accountId: 'acc-tx-300',
                referenceType: AccountTransactionReferenceType::WITHDRAW,
                referenceId: 'wd-tx-301',
                direction: AccountTransactionDirection::DEBIT,
                amount: Money::fromDecimal('20.00'),
                balanceBefore: Money::fromDecimal('105.00'),
                balanceAfter: Money::fromDecimal('85.00'),
                createdAt: new DateTimeImmutable('2026-03-28 13:25:00+00:00'),
            )
        );

        self::assertSame(2, Db::table('account_transaction')->count());
    }

    public function testFindByWithdrawIdReturnsNullWhenTransactionDoesNotExist(): void
    {
        $repository = $this->container->get(AccountTransactionRepository::class);

        self::assertNull($repository->findByWithdrawId('missing-transaction'));
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Repository Test Account',
            'balance' => '1000.00',
            'created_at' => '2026-03-28 12:00:00.000000',
            'updated_at' => '2026-03-28 12:00:00.000000',
        ]);
    }
}
