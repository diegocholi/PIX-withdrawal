<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateAccountWithdrawPixTableMigration extends Migration
{
    public function up(): void
    {
        $schema = Db::connection($this->connectionName())->getSchemaBuilder();

        if ($schema->hasTable('account_withdraw_pix')) {
            return;
        }

        $schema->create('account_withdraw_pix', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->char('account_withdraw_id', 36)->primary();
            $table->string('type', 32);
            $table->string('key', 255);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);

            $table->foreign('account_withdraw_id', 'account_withdraw_pix_withdraw_id_foreign')
                ->references('id')
                ->on('account_withdraw');

            $table->index(
                ['type', 'key'],
                'account_withdraw_pix_type_key_index'
            );
        });
    }

    public function down(): void
    {
        Db::connection($this->connectionName())
            ->getSchemaBuilder()
            ->dropIfExists('account_withdraw_pix');
    }

    private function connectionName(): ?string
    {
        return $this->getConnection() !== '' ? $this->getConnection() : null;
    }
}
