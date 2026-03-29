<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations;

use Hyperf\Database\Exception\QueryException;
use Hyperf\DbConnection\Db;

final class DataSchemaIntegrityTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testWithdrawTableRejectsMissingAccountReference(): void
    {
        $this->expectException(QueryException::class);

        Db::table('account_withdraw')->insert([
            'id' => 'wd-fk-100',
            'account_id' => 'missing-account',
            'method' => 'PIX',
            'amount' => '10.00',
            'scheduled' => 0,
            'scheduled_for' => null,
            'status' => 'PENDING',
            'error_reason' => null,
            'requested_at' => '2026-03-29 10:00:00.000000',
            'queued_at' => null,
            'processing_started_at' => null,
            'processed_at' => null,
            'correlation_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'idempotency_key' => 'withdraw:create:v1:fk-test',
            'retry_count' => 0,
            'last_retry_at' => null,
            'created_at' => '2026-03-29 10:00:00.000000',
            'updated_at' => '2026-03-29 10:00:00.000000',
        ]);
    }

    public function testWithdrawTableRejectsDuplicateIdempotencyKey(): void
    {
        $this->insertAccount('acc-int-100');

        $payload = [
            'account_id' => 'acc-int-100',
            'method' => 'PIX',
            'amount' => '10.00',
            'scheduled' => 0,
            'scheduled_for' => null,
            'status' => 'PENDING',
            'error_reason' => null,
            'requested_at' => '2026-03-29 10:00:00.000000',
            'queued_at' => null,
            'processing_started_at' => null,
            'processed_at' => null,
            'correlation_id' => 'aaaaaaaa-aaaa-aaaa-aaaa-aaaaaaaaaaaa',
            'idempotency_key' => 'withdraw:create:v1:duplicate-test',
            'retry_count' => 0,
            'last_retry_at' => null,
            'created_at' => '2026-03-29 10:00:00.000000',
            'updated_at' => '2026-03-29 10:00:00.000000',
        ];

        Db::table('account_withdraw')->insert(['id' => 'wd-unique-100', ...$payload]);

        $this->expectException(QueryException::class);

        Db::table('account_withdraw')->insert(['id' => 'wd-unique-200', ...$payload]);
    }

    public function testWithdrawPixTableRejectsMissingWithdrawReference(): void
    {
        $this->expectException(QueryException::class);

        Db::table('account_withdraw_pix')->insert([
            'account_withdraw_id' => 'missing-withdraw',
            'type' => 'EMAIL',
            'key' => 'user@example.com',
            'created_at' => '2026-03-29 10:10:00.000000',
            'updated_at' => '2026-03-29 10:10:00.000000',
        ]);
    }

    public function testAccountTransactionTableRejectsMissingAccountReference(): void
    {
        $this->expectException(QueryException::class);

        Db::table('account_transaction')->insert([
            'account_id' => 'missing-account',
            'reference_type' => 'WITHDRAW',
            'reference_id' => 'wd-tx-fk-100',
            'direction' => 'DEBIT',
            'amount' => '15.00',
            'balance_before' => '30.00',
            'balance_after' => '15.00',
            'created_at' => '2026-03-29 10:20:00.000000',
        ]);
    }

    private function insertAccount(string $accountId): void
    {
        Db::table('account')->insert([
            'id' => $accountId,
            'name' => 'Schema Integrity Account',
            'balance' => '100.00',
            'created_at' => '2026-03-29 09:00:00.000000',
            'updated_at' => '2026-03-29 09:00:00.000000',
        ]);
    }
}
