<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Transactions;

use Hyperf\DbConnection\Db;
use RuntimeException;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlTransactionManager;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class MySqlTransactionManagerTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testRunCommitsChangesAndReturnsCallbackResult(): void
    {
        $manager = $this->transactionManager();

        $result = $manager->run(function (): string {
            Db::table('account')->insert([
                'id' => 'acc-transaction-commit',
                'name' => 'Transactional Account',
                'balance' => '120.00',
                'created_at' => '2026-03-28 13:00:00',
                'updated_at' => '2026-03-28 13:00:00',
            ]);

            return 'committed';
        });

        self::assertSame('committed', $result);
        self::assertSame(1, Db::table('account')->where('id', 'acc-transaction-commit')->count());
    }

    public function testRunRollsBackChangesWhenCallbackThrows(): void
    {
        $manager = $this->transactionManager();

        try {
            $manager->run(function (): never {
                Db::table('account')->insert([
                    'id' => 'acc-transaction-rollback',
                    'name' => 'Rollback Account',
                    'balance' => '75.00',
                    'created_at' => '2026-03-28 13:05:00',
                    'updated_at' => '2026-03-28 13:05:00',
                ]);

                throw new RuntimeException('boom');
            });

            self::fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            self::assertSame('boom', $exception->getMessage());
        }

        self::assertSame(0, Db::table('account')->where('id', 'acc-transaction-rollback')->count());
    }

    private function transactionManager(): MySqlTransactionManager
    {
        return new MySqlTransactionManager($this->container->get(\Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig::class));
    }
}
