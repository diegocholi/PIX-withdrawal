<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Transactions;

use DateTimeImmutable;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlAtomicAccountDebit;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlAtomicAccountDebitTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testExecuteDebitsBalanceAtomicallyAndReturnsUpdatedAccount(): void
    {
        $this->insertAccount(
            id: 'acc-atomic-success',
            balance: '100.00',
            createdAt: '2026-03-28 12:00:00',
            updatedAt: '2026-03-28 12:00:00',
        );

        $account = Account::reconstitute(
            id: 'acc-atomic-success',
            name: 'Atomic Account',
            balance: Money::fromDecimal('100.00'),
            createdAt: new DateTimeImmutable('2026-03-28 12:00:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-03-28 12:00:00+00:00'),
        );

        $result = $this->atomicDebit()->execute(
            $account,
            Money::fromDecimal('25.50'),
            new DateTimeImmutable('2026-03-28 12:05:00+00:00'),
        );

        self::assertTrue($result->isSuccess());

        /** @var Account $debitedAccount */
        $debitedAccount = $result->value();
        self::assertSame('74.50', $debitedAccount->balance()->toDecimal());
        self::assertSame('74.50', (string) Db::table('account')->where('id', 'acc-atomic-success')->value('balance'));
        self::assertSame('2026-03-28 12:05:00.000000', (string) Db::table('account')->where('id', 'acc-atomic-success')->value('updated_at'));
    }

    public function testExecuteReturnsFailureWhenBalanceIsInsufficientWithoutMutatingRow(): void
    {
        $this->insertAccount(
            id: 'acc-atomic-failure',
            balance: '10.00',
            createdAt: '2026-03-28 12:10:00',
            updatedAt: '2026-03-28 12:10:00',
        );

        $account = Account::reconstitute(
            id: 'acc-atomic-failure',
            name: 'Atomic Account',
            balance: Money::fromDecimal('10.00'),
            createdAt: new DateTimeImmutable('2026-03-28 12:10:00+00:00'),
            updatedAt: new DateTimeImmutable('2026-03-28 12:10:00+00:00'),
        );

        $result = $this->atomicDebit()->execute(
            $account,
            Money::fromDecimal('25.00'),
            new DateTimeImmutable('2026-03-28 12:15:00+00:00'),
        );

        self::assertTrue($result->isFailure());
        self::assertInstanceOf(InsufficientAccountBalance::class, $result->error());
        self::assertSame('10.00', (string) Db::table('account')->where('id', 'acc-atomic-failure')->value('balance'));
        self::assertSame('2026-03-28 12:10:00.000000', (string) Db::table('account')->where('id', 'acc-atomic-failure')->value('updated_at'));
    }

    private function atomicDebit(): MySqlAtomicAccountDebit
    {
        return new MySqlAtomicAccountDebit($this->container->get(\Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig::class));
    }

    private function insertAccount(string $id, string $balance, string $createdAt, string $updatedAt): void
    {
        Db::table('account')->insert([
            'id' => $id,
            'name' => 'Atomic Account',
            'balance' => $balance,
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ]);
    }
}
