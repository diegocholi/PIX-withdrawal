<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations;

use Hyperf\DbConnection\Db;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Tecnofit\PixWithdrawal\Plugins\Data\Migrations\DataSchemaMigrationPlan;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

abstract class DataSchemaMigrationIntegrationTestCase extends TestCase
{
    protected ContainerInterface $container;

    protected function setUp(): void
    {
        parent::setUp();

        $this->applyLocalEnvironment();
        $this->container = (new HyperfContainerFactory())->create();
        $this->resetSchema();
        (new DataSchemaMigrationPlan())->up();
    }

    protected function tearDown(): void
    {
        $this->resetSchema();
        $this->clearEnvironmentOverrides();

        parent::tearDown();
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    protected function describeTable(string $table): array
    {
        $rows = Db::select(sprintf('SHOW COLUMNS FROM `%s`', $table));
        $columns = [];

        foreach ($rows as $row) {
            $field = (string) ($row->Field ?? '');
            $columns[$field] = [
                'type' => $row->Type ?? null,
                'nullable' => ($row->Null ?? 'NO') === 'YES',
                'key' => $row->Key ?? null,
                'default' => $row->Default ?? null,
                'extra' => $row->Extra ?? null,
            ];
        }

        return $columns;
    }

    /**
     * @return list<string>
     */
    protected function indexNames(string $table): array
    {
        $rows = Db::select(sprintf('SHOW INDEX FROM `%s`', $table));
        $indexNames = [];

        foreach ($rows as $row) {
            $indexName = (string) ($row->Key_name ?? '');

            if ($indexName === '') {
                continue;
            }

            $indexNames[$indexName] = $indexName;
        }

        return array_values($indexNames);
    }

    /**
     * @return array<string, string>
     */
    protected function foreignKeys(string $table): array
    {
        $database = (string) getenv('DB_DATABASE');
        $rows = Db::select(
            'SELECT CONSTRAINT_NAME, REFERENCED_TABLE_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME = ?
               AND REFERENCED_TABLE_NAME IS NOT NULL',
            [$database, $table],
        );

        $constraints = [];

        foreach ($rows as $row) {
            $constraints[(string) $row->CONSTRAINT_NAME] = (string) $row->REFERENCED_TABLE_NAME;
        }

        return $constraints;
    }

    private function resetSchema(): void
    {
        Db::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            (new DataSchemaMigrationPlan())->down();
        } finally {
            Db::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    private function applyLocalEnvironment(): void
    {
        putenv('APP_ENV=local');
        putenv('DB_HOST=mysql');
        putenv('DB_PORT=3306');
        putenv('DB_DATABASE=pix_withdrawal');
        putenv('DB_USERNAME=pix_withdrawal');
        putenv('DB_PASSWORD=pix_withdrawal');
    }

    private function clearEnvironmentOverrides(): void
    {
        foreach ([
            'APP_ENV',
            'DB_HOST',
            'DB_PORT',
            'DB_DATABASE',
            'DB_USERNAME',
            'DB_PASSWORD',
        ] as $environmentVariable) {
            putenv($environmentVariable);
        }
    }
}
