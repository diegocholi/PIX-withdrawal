<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class MySqlConnectivityCheck
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function run(): MySqlConnectivityCheckResult
    {
        try {
            /** @var object{ping?: int|string|null} $pingRow */
            $pingRow = Db::connection($this->connectionConfig->name())->selectOne('SELECT 1 AS ping');
            /** @var object{server_version?: string|null} $versionRow */
            $versionRow = Db::connection($this->connectionConfig->name())->selectOne('SELECT VERSION() AS server_version');
        } catch (\Throwable $throwable) {
            throw new TransientInfrastructureException('MySQL connectivity check failed.', 0, $throwable);
        }

        return new MySqlConnectivityCheckResult(
            connectionName: $this->connectionConfig->name(),
            driver: $this->connectionConfig->driver(),
            host: $this->connectionConfig->host(),
            port: $this->connectionConfig->port(),
            database: $this->connectionConfig->database(),
            ping: (int) ($pingRow->ping ?? 0),
            serverVersion: trim((string) ($versionRow->server_version ?? 'unknown')),
        );
    }
}
