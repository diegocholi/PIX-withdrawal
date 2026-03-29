<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateAccountTableMigration extends Migration
{
    public function up(): void
    {
        $schema = Db::connection($this->connectionName())->getSchemaBuilder();

        if ($schema->hasTable('account')) {
            return;
        }

        $schema->create('account', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->char('id', 36)->primary();
            $table->string('name', 150);
            $table->decimal('balance', 18, 2);
            $table->dateTime('created_at', 6);
            $table->dateTime('updated_at', 6);
        });
    }

    public function down(): void
    {
        Db::connection($this->connectionName())
            ->getSchemaBuilder()
            ->dropIfExists('account');
    }

    private function connectionName(): ?string
    {
        return $this->getConnection() !== '' ? $this->getConnection() : null;
    }
}
