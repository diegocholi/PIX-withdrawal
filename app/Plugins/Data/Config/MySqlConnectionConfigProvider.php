<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Config;

use Hyperf\Contract\ConfigInterface;

final readonly class MySqlConnectionConfigProvider
{
    public function __construct(
        private ConfigInterface $config,
        private MySqlConnectionConfigValidator $validator,
    ) {
    }

    public function defaultConnectionName(): string
    {
        $defaultConnectionName = $this->config->get('databases.default_connection');

        if (is_string($defaultConnectionName) && trim($defaultConnectionName) !== '') {
            return trim($defaultConnectionName);
        }

        $legacyDefaultConnectionName = $this->config->get('databases.default');

        if (is_string($legacyDefaultConnectionName) && trim($legacyDefaultConnectionName) !== '') {
            return trim($legacyDefaultConnectionName);
        }

        return 'default';
    }

    public function defaultConnection(): MySqlConnectionConfig
    {
        $connectionName = $this->defaultConnectionName();
        /** @var array<string, mixed> $connection */
        $connection = $this->config->get(sprintf('databases.connections.%s', $connectionName), []);

        if ($connection === []) {
            /** @var array<string, mixed> $fallbackConnection */
            $fallbackConnection = $this->config->get(sprintf('databases.%s', $connectionName), []);
            $connection = $fallbackConnection;
        }

        $config = MySqlConnectionConfig::fromArray($connectionName, $connection);
        $this->validator->validate($config);

        return $config;
    }
}
