<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateAccountWithdrawTableMigration extends Migration
{
    public function up(): void
    {
        $schema = Db::connection($this->connectionName())->getSchemaBuilder();

        if ($schema->hasTable('account_withdraw')) {
            return;
        }

        $schema->create('account_withdraw', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->char('id', 36)->primary();
            $table->char('account_id', 36);
            $table->string('method', 32);
            $table->decimal('amount', 18, 2);
            $table->boolean('scheduled');
            $table->dateTime('scheduled_for', 6)->nullable();
            $table->string('status', 64);
            $table->string('error_reason', 255)->nullable();
            $table->dateTime('requested_at', 6);
            $table->dateTime('queued_at', 6)->nullable();
            $table->dateTime('processing_started_at', 6)->nullable();
            $table->dateTime('processed_at', 6)->nullable();
            $table->char('correlation_id', 36);
            $table->string('idempotency_key', 83)->unique('account_withdraw_idempotency_key_unique');
            $table->string('duplicate_guard_fingerprint', 83)->default('');
            $table->unsignedInteger('retry_count')->default(0);
            $table->dateTime('last_retry_at', 6)->nullable();
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);

            $table->foreign('account_id', 'account_withdraw_account_id_foreign')
                ->references('id')
                ->on('account');

            $table->index(
                ['account_id', 'status', 'requested_at'],
                'account_withdraw_account_status_requested_at_index'
            );
            $table->index(
                ['status', 'scheduled_for'],
                'account_withdraw_status_scheduled_for_index'
            );
            $table->index(
                ['duplicate_guard_fingerprint', 'created_at'],
                'account_withdraw_duplicate_guard_created_at_index'
            );
        });
    }

    public function down(): void
    {
        Db::connection($this->connectionName())
            ->getSchemaBuilder()
            ->dropIfExists('account_withdraw');
    }

    private function connectionName(): ?string
    {
        return $this->getConnection() !== '' ? $this->getConnection() : null;
    }
}
