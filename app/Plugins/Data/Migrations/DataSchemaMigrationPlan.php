<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Migrations;

use Hyperf\Database\Migrations\Migration;

final class DataSchemaMigrationPlan
{
    /**
     * @return list<Migration>
     */
    public function migrations(): array
    {
        return [
            new CreateAccountTableMigration(),
            new CreateAccountWithdrawTableMigration(),
            new CreateAccountWithdrawPixTableMigration(),
            new CreateAccountTransactionTableMigration(),
            new CreateSeedExecutionTableMigration(),
        ];
    }

    public function up(): void
    {
        foreach ($this->migrations() as $migration) {
            $migration->up();
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->migrations()) as $migration) {
            $migration->down();
        }
    }
}
