<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Diagnostic;

final readonly class MySqlConnectivityCheckResult
{
    public function __construct(
        private string $connectionName,
        private string $driver,
        private string $host,
        private int $port,
        private string $database,
        private int $ping,
        private string $serverVersion,
    ) {
    }

    public function connectionName(): string
    {
        return $this->connectionName;
    }

    public function driver(): string
    {
        return $this->driver;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function database(): string
    {
        return $this->database;
    }

    public function ping(): int
    {
        return $this->ping;
    }

    public function serverVersion(): string
    {
        return $this->serverVersion;
    }
}
