<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\CreateAccountTableMigration;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\CreateAccountTransactionTableMigration;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\CreateAccountWithdrawPixTableMigration;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\CreateAccountWithdrawTableMigration;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\CreateSeedExecutionTableMigration;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\DataSchemaMigrationPlan;

final class DataSchemaMigrationPlanTest extends TestCase
{
    public function testPlanKeepsSchemaCreationOrderStable(): void
    {
        $migrations = (new DataSchemaMigrationPlan())->migrations();

        self::assertCount(5, $migrations);
        self::assertInstanceOf(CreateAccountTableMigration::class, $migrations[0]);
        self::assertInstanceOf(CreateAccountWithdrawTableMigration::class, $migrations[1]);
        self::assertInstanceOf(CreateAccountWithdrawPixTableMigration::class, $migrations[2]);
        self::assertInstanceOf(CreateAccountTransactionTableMigration::class, $migrations[3]);
        self::assertInstanceOf(CreateSeedExecutionTableMigration::class, $migrations[4]);
    }
}
