<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

final readonly class MailConfigValidator
{
    public function __construct(private ProviderConfigProvider $providerConfigProvider)
    {
    }

    public function validate(MailConfig $config): void
    {
        if ($this->providerConfigProvider->providerEnvironment() === ProviderEnvironment::TEST) {
            return;
        }

        if ($config->defaultMailer() !== 'smtp') {
            throw new \InvalidArgumentException('Mail default configuration must be "smtp" outside test environment.');
        }

        if (trim($config->host()) === '') {
            throw new \InvalidArgumentException('SMTP host configuration must be non-empty outside test environment.');
        }

        if ($config->port() <= 0) {
            throw new \InvalidArgumentException('SMTP port configuration must be greater than zero outside test environment.');
        }

        if (trim($config->fromAddress()) === '') {
            throw new \InvalidArgumentException('SMTP default sender address must be non-empty outside test environment.');
        }

        if ($config->connectTimeoutSeconds() <= 0 || $config->readTimeoutSeconds() <= 0) {
            throw new \InvalidArgumentException('SMTP timeout configuration must define positive connect and read timeouts.');
        }

        if ($config->username() !== null && $config->password() === null) {
            throw new \InvalidArgumentException('SMTP authentication password must be configured when username is provided.');
        }

        if ($config->username() === null && $config->password() !== null) {
            throw new \InvalidArgumentException('SMTP authentication username must be configured when password is provided.');
        }
    }
}
