<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations;

use Hyperf\DbConnection\Db;

final class DataSchemaMigrationTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testCreatesAccountTableWithPrimaryKeyAndMandatoryColumns(): void
    {
        self::assertTrue(Db::connection()->getSchemaBuilder()->hasTable('account'));

        $columns = $this->describeTable('account');

        self::assertSame('char(36)', $columns['id']['type']);
        self::assertFalse($columns['id']['nullable']);
        self::assertSame('PRI', $columns['id']['key']);
        self::assertSame('varchar(150)', $columns['name']['type']);
        self::assertFalse($columns['name']['nullable']);
        self::assertSame('decimal(18,2)', $columns['balance']['type']);
        self::assertFalse($columns['balance']['nullable']);
        self::assertSame('datetime(6)', $columns['created_at']['type']);
        self::assertSame('datetime(6)', $columns['updated_at']['type']);
    }

    public function testCreatesWithdrawTableWithForeignKeyAndOperationalColumns(): void
    {
        self::assertTrue(Db::connection()->getSchemaBuilder()->hasTable('account_withdraw'));

        $columns = $this->describeTable('account_withdraw');
        $indexNames = $this->indexNames('account_withdraw');
        $foreignKeys = $this->foreignKeys('account_withdraw');

        self::assertSame('char(36)', $columns['id']['type']);
        self::assertSame('char(36)', $columns['account_id']['type']);
        self::assertSame('varchar(32)', $columns['method']['type']);
        self::assertSame('decimal(18,2)', $columns['amount']['type']);
        self::assertSame('tinyint(1)', $columns['scheduled']['type']);
        self::assertTrue($columns['scheduled_for']['nullable']);
        self::assertSame('varchar(64)', $columns['status']['type']);
        self::assertTrue($columns['error_reason']['nullable']);
        self::assertSame('char(36)', $columns['correlation_id']['type']);
        self::assertSame('varchar(83)', $columns['idempotency_key']['type']);
        self::assertSame('varchar(83)', $columns['duplicate_guard_fingerprint']['type']);
        self::assertSame('', (string) $columns['duplicate_guard_fingerprint']['default']);
        self::assertSame('0', (string) $columns['retry_count']['default']);
        self::assertContains('account_withdraw_idempotency_key_unique', $indexNames);
        self::assertContains('account_withdraw_account_status_requested_at_index', $indexNames);
        self::assertContains('account_withdraw_status_scheduled_for_index', $indexNames);
        self::assertContains('account_withdraw_duplicate_guard_created_at_index', $indexNames);
        self::assertSame('account', $foreignKeys['account_withdraw_account_id_foreign'] ?? null);
    }

    public function testCreatesWithdrawPixTableBoundToWithdraw(): void
    {
        self::assertTrue(Db::connection()->getSchemaBuilder()->hasTable('account_withdraw_pix'));

        $columns = $this->describeTable('account_withdraw_pix');
        $indexNames = $this->indexNames('account_withdraw_pix');
        $foreignKeys = $this->foreignKeys('account_withdraw_pix');

        self::assertSame('char(36)', $columns['account_withdraw_id']['type']);
        self::assertSame('PRI', $columns['account_withdraw_id']['key']);
        self::assertSame('varchar(32)', $columns['type']['type']);
        self::assertSame('varchar(255)', $columns['key']['type']);
        self::assertContains('account_withdraw_pix_type_key_index', $indexNames);
        self::assertSame('account_withdraw', $foreignKeys['account_withdraw_pix_withdraw_id_foreign'] ?? null);
    }

    public function testCreatesAccountTransactionTableWithReferenceIndexAndAccountForeignKey(): void
    {
        self::assertTrue(Db::connection()->getSchemaBuilder()->hasTable('account_transaction'));

        $columns = $this->describeTable('account_transaction');
        $indexNames = $this->indexNames('account_transaction');
        $foreignKeys = $this->foreignKeys('account_transaction');

        self::assertStringStartsWith('bigint', (string) $columns['id']['type']);
        self::assertSame('auto_increment', $columns['id']['extra']);
        self::assertSame('char(36)', $columns['account_id']['type']);
        self::assertSame('varchar(32)', $columns['reference_type']['type']);
        self::assertSame('char(36)', $columns['reference_id']['type']);
        self::assertSame('varchar(16)', $columns['direction']['type']);
        self::assertSame('decimal(18,2)', $columns['amount']['type']);
        self::assertSame('decimal(18,2)', $columns['balance_before']['type']);
        self::assertSame('decimal(18,2)', $columns['balance_after']['type']);
        self::assertContains('account_transaction_reference_type_reference_id_index', $indexNames);
        self::assertSame('account', $foreignKeys['account_transaction_account_id_foreign'] ?? null);
    }

    public function testCreatesSeedExecutionTableForBootstrapSeedTracking(): void
    {
        self::assertTrue(Db::connection()->getSchemaBuilder()->hasTable('seed_execution'));

        $columns = $this->describeTable('seed_execution');

        self::assertSame('varchar(120)', $columns['seed_name']['type']);
        self::assertSame('PRI', $columns['seed_name']['key']);
        self::assertSame('datetime(6)', $columns['executed_at']['type']);
        self::assertFalse($columns['executed_at']['nullable']);
    }
}
