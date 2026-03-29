<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateAccountTransactionTableMigration extends Migration
{
    public function up(): void
    {
        $schema = Db::connection($this->connectionName())->getSchemaBuilder();

        if ($schema->hasTable('account_transaction')) {
            return;
        }

        $schema->create('account_transaction', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->bigIncrements('id');
            $table->char('account_id', 36);
            $table->string('reference_type', 32);
            $table->char('reference_id', 36);
            $table->string('direction', 16);
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_before', 18, 2);
            $table->decimal('balance_after', 18, 2);
            $table->dateTime('created_at', 6);

            $table->foreign('account_id', 'account_transaction_account_id_foreign')
                ->references('id')
                ->on('account');

            $table->index(
                ['reference_type', 'reference_id'],
                'account_transaction_reference_type_reference_id_index'
            );
        });
    }

    public function down(): void
    {
        Db::connection($this->connectionName())
            ->getSchemaBuilder()
            ->dropIfExists('account_transaction');
    }

    private function connectionName(): ?string
    {
        return $this->getConnection() !== '' ? $this->getConnection() : null;
    }
}
