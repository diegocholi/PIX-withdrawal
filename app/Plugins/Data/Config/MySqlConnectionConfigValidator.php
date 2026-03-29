<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Config;

final readonly class MySqlConnectionConfigValidator
{
    public function validate(MySqlConnectionConfig $config): void
    {
        if ($config->name() === '') {
            throw new \InvalidArgumentException('MySQL connection name must be non-empty.');
        }

        if ($config->driver() !== 'mysql') {
            throw new \InvalidArgumentException('MySQL connection driver must be "mysql".');
        }

        if ($config->host() === '') {
            throw new \InvalidArgumentException('MySQL connection host must be non-empty.');
        }

        if ($config->port() <= 0) {
            throw new \InvalidArgumentException('MySQL connection port must be greater than zero.');
        }

        if ($config->database() === '') {
            throw new \InvalidArgumentException('MySQL connection database must be non-empty.');
        }

        if ($config->username() === '') {
            throw new \InvalidArgumentException('MySQL connection username must be non-empty.');
        }
    }
}
