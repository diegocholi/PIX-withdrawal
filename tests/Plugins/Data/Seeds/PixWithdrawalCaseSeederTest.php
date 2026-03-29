<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Seeds;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Plugins\Data\Seeds\PixWithdrawalCaseSeeder;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class PixWithdrawalCaseSeederTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testSeedCreatesCanonicalCaseDataForImmediateScheduledAndInsufficientWithdrawScenarios(): void
    {
        $seeder = $this->container->get(PixWithdrawalCaseSeeder::class);

        $result = $seeder->seed();

        self::assertTrue($result->wasApplied());
        self::assertSame(4, Db::table('account')->count());
        self::assertSame(3, Db::table('account_withdraw')->count());
        self::assertSame(3, Db::table('account_withdraw_pix')->count());
        self::assertSame(1, Db::table('account_transaction')->count());
        self::assertSame(1, Db::table('seed_execution')->count());
        self::assertSame('849.25', Db::table('account')->where('id', '11111111-1111-4111-8111-111111111111')->value('balance'));
        self::assertSame('DONE', Db::table('account_withdraw')->where('id', 'aaaaaaa1-1111-4111-8111-111111111111')->value('status'));
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', 'bbbbbbb2-2222-4222-8222-222222222222')->value('status'));
        self::assertSame(
            'FAILED_INSUFFICIENT_FUNDS',
            Db::table('account_withdraw')->where('id', 'ccccccc3-3333-4333-8333-333333333333')->value('status')
        );
        self::assertSame(
            'pix.immediate@example.com',
            Db::table('account_withdraw_pix')->where('account_withdraw_id', 'aaaaaaa1-1111-4111-8111-111111111111')->value('key')
        );
    }

    public function testSeedSkipsWhenCaseDataWasAlreadyAppliedToDatabase(): void
    {
        $seeder = $this->container->get(PixWithdrawalCaseSeeder::class);

        $firstRun = $seeder->seed();
        $secondRun = $seeder->seed();

        self::assertTrue($firstRun->wasApplied());
        self::assertTrue($secondRun->wasSkipped());
        self::assertSame(1, Db::table('seed_execution')->count());
        self::assertSame(4, Db::table('account')->count());
        self::assertSame(3, Db::table('account_withdraw')->count());
        self::assertSame(3, Db::table('account_withdraw_pix')->count());
        self::assertSame(1, Db::table('account_transaction')->count());
    }
}
