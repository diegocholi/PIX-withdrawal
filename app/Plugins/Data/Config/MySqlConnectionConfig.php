<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Config;

final readonly class MySqlConnectionConfig
{
    public function __construct(
        private string $name,
        private string $driver,
        private string $host,
        private int $port,
        private string $database,
        private string $username,
        private string $password,
        private string $charset,
        private string $collation,
        private string $prefix,
        private bool $strict,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(string $name, array $config): self
    {
        return new self(
            name: trim($name),
            driver: trim((string) ($config['driver'] ?? '')),
            host: trim((string) ($config['host'] ?? '')),
            port: max(0, (int) ($config['port'] ?? 0)),
            database: trim((string) ($config['database'] ?? '')),
            username: trim((string) ($config['username'] ?? '')),
            password: (string) ($config['password'] ?? ''),
            charset: trim((string) ($config['charset'] ?? 'utf8mb4')),
            collation: trim((string) ($config['collation'] ?? 'utf8mb4_unicode_ci')),
            prefix: (string) ($config['prefix'] ?? ''),
            strict: (bool) ($config['strict'] ?? true),
        );
    }

    public function name(): string
    {
        return $this->name;
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

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }

    public function charset(): string
    {
        return $this->charset;
    }

    public function collation(): string
    {
        return $this->collation;
    }

    public function prefix(): string
    {
        return $this->prefix;
    }

    public function strict(): bool
    {
        return $this->strict;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function toArray(): array
    {
        return [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'charset' => $this->charset,
            'collation' => $this->collation,
            'prefix' => $this->prefix,
            'strict' => $this->strict,
        ];
    }
}
