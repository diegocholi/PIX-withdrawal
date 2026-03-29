<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

final readonly class MailConfig
{
    public function __construct(
        private ?string $defaultMailer,
        private string $transport,
        private string $host,
        private int $port,
        private ?string $username,
        private ?string $password,
        private ?string $encryption,
        private string $fromAddress,
        private string $fromName,
        private int $connectTimeoutSeconds,
        private int $readTimeoutSeconds,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        /** @var array<string, mixed> $smtp */
        $smtp = is_array($config['mailers']['smtp'] ?? null) ? $config['mailers']['smtp'] : [];
        /** @var array<string, mixed> $from */
        $from = is_array($smtp['from'] ?? null) ? $smtp['from'] : [];
        /** @var array<string, mixed> $timeouts */
        $timeouts = is_array($smtp['timeouts'] ?? null) ? $smtp['timeouts'] : [];

        $defaultMailer = trim((string) ($config['default'] ?? ''));
        $username = trim((string) ($smtp['username'] ?? ''));
        $password = trim((string) ($smtp['password'] ?? ''));
        $encryption = trim((string) ($smtp['encryption'] ?? ''));

        return new self(
            defaultMailer: $defaultMailer === '' ? null : $defaultMailer,
            transport: trim((string) ($smtp['transport'] ?? 'smtp')),
            host: trim((string) ($smtp['host'] ?? '')),
            port: max(0, (int) ($smtp['port'] ?? 0)),
            username: $username === '' ? null : $username,
            password: $password === '' ? null : $password,
            encryption: $encryption === '' ? null : $encryption,
            fromAddress: trim((string) ($from['address'] ?? '')),
            fromName: trim((string) ($from['name'] ?? '')),
            connectTimeoutSeconds: max(0, (int) ($timeouts['connect_seconds'] ?? 5)),
            readTimeoutSeconds: max(0, (int) ($timeouts['read_seconds'] ?? 5)),
        );
    }

    public function defaultMailer(): ?string
    {
        return $this->defaultMailer;
    }

    public function transport(): string
    {
        return $this->transport;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function username(): ?string
    {
        return $this->username;
    }

    public function password(): ?string
    {
        return $this->password;
    }

    public function encryption(): ?string
    {
        return $this->encryption;
    }

    public function fromAddress(): string
    {
        return $this->fromAddress;
    }

    public function fromName(): string
    {
        return $this->fromName;
    }

    public function connectTimeoutSeconds(): int
    {
        return $this->connectTimeoutSeconds;
    }

    public function readTimeoutSeconds(): int
    {
        return $this->readTimeoutSeconds;
    }

    public function hasAuthentication(): bool
    {
        return $this->username !== null || $this->password !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'default' => $this->defaultMailer,
            'mailers' => $this->defaultMailer === null ? [] : [
                'smtp' => [
                    'transport' => $this->transport,
                    'host' => $this->host,
                    'port' => $this->port,
                    'username' => $this->username,
                    'password' => $this->password,
                    'encryption' => $this->encryption,
                    'from' => [
                        'address' => $this->fromAddress,
                        'name' => $this->fromName,
                    ],
                    'timeouts' => [
                        'connect_seconds' => $this->connectTimeoutSeconds,
                        'read_seconds' => $this->readTimeoutSeconds,
                    ],
                ],
            ],
        ];
    }
}
