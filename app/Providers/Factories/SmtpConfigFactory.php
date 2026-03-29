<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class SmtpConfigFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private MailConfigValidator $mailConfigValidator,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function create(): MailConfig
    {
        $config = MailConfig::fromArray($this->providerConfigProvider->mail());
        $this->mailConfigValidator->validate($config);
        $this->logLoadedConfig($config);

        return $config;
    }

    private function logLoadedConfig(MailConfig $config): void
    {
        $this->structuredLogger->info(
            'providers.bootstrap.mail.loaded',
            new LogContext(
                correlationId: 'providers-bootstrap',
                context: [
                    'provider' => 'providers.bootstrap',
                    'operation' => 'providers.bootstrap.mail',
                    'environment' => $this->providerConfigProvider->providerEnvironment(),
                    'default_mailer' => $config->defaultMailer(),
                    'transport' => $config->transport(),
                    'host' => $config->host(),
                    'port' => $config->port(),
                    'encryption' => $config->encryption(),
                    'has_authentication' => $config->hasAuthentication(),
                    'from_address' => $config->fromAddress(),
                    'connect_timeout_seconds' => $config->connectTimeoutSeconds(),
                    'read_timeout_seconds' => $config->readTimeoutSeconds(),
                ],
            ),
        );
    }
}
