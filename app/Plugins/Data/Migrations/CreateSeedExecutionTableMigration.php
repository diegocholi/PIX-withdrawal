<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;

final class CreateSeedExecutionTableMigration extends Migration
{
    public function up(): void
    {
        $schema = Db::connection($this->connectionName())->getSchemaBuilder();

        if ($schema->hasTable('seed_execution')) {
            return;
        }

        $schema->create('seed_execution', static function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->string('seed_name', 120)->primary();
            $table->dateTime('executed_at', 6);
        });
    }

    public function down(): void
    {
        Db::connection($this->connectionName())
            ->getSchemaBuilder()
            ->dropIfExists('seed_execution');
    }

    private function connectionName(): ?string
    {
        return $this->getConnection() !== '' ? $this->getConnection() : null;
    }
}
