<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Seeds;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Plugins\Data\Seeds\DevelopmentAccountSeeder;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class DevelopmentAccountSeederTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSeedCreatesDevelopmentAccountsWithDistinctBalances(): void
    {
        $seeder = $this->container->get(DevelopmentAccountSeeder::class);

        $seeder->seed();

        $rows = Db::table('account')
            ->orderBy('id')
            ->get()
            ->map(static fn (object $row): array => [
                'id' => $row->id,
                'name' => $row->name,
                'balance' => $row->balance,
            ])
            ->all();

        self::assertSame([
            [
                'id' => '11111111-1111-4111-8111-111111111111',
                'name' => 'Seed Immediate Success Wallet',
                'balance' => '849.25',
            ],
            [
                'id' => '22222222-2222-4222-8222-222222222222',
                'name' => 'Seed Scheduled Pending Wallet',
                'balance' => '250.50',
            ],
            [
                'id' => '33333333-3333-4333-8333-333333333333',
                'name' => 'Seed Scheduled Insufficient Wallet',
                'balance' => '40.00',
            ],
            [
                'id' => '44444444-4444-4444-8444-444444444444',
                'name' => 'Seed Empty Balance Wallet',
                'balance' => '0.00',
            ],
        ], $rows);
    }

    public function testSeedIsRepeatableWithoutDuplicatingAccounts(): void
    {
        $seeder = $this->container->get(DevelopmentAccountSeeder::class);

        $seeder->seed();
        $seeder->seed();

        self::assertSame(4, Db::table('account')->count());
        self::assertSame(
            '2026-03-29 12:00:00.000000',
            Db::table('account')->where('id', '11111111-1111-4111-8111-111111111111')->value('created_at'),
        );
    }
}
