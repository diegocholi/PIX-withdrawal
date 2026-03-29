<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfigValidator;

final readonly class KafkaConfigFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private KafkaConfigValidator $kafkaConfigValidator,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function create(): KafkaConfig
    {
        $config = KafkaConfig::fromArray($this->providerConfigProvider->kafka());
        $this->kafkaConfigValidator->validate($config);
        $this->logLoadedConfig($config);

        return $config;
    }

    private function logLoadedConfig(KafkaConfig $config): void
    {
        $this->structuredLogger->info(
            'providers.bootstrap.kafka.loaded',
            new LogContext(
                correlationId: 'providers-bootstrap',
                context: [
                    'provider' => 'providers.bootstrap',
                    'operation' => 'providers.bootstrap.kafka',
                    'environment' => $this->providerConfigProvider->providerEnvironment(),
                    'client_id' => $config->clientId(),
                    'brokers_count' => count($config->brokers()),
                    'producer_keys' => array_keys($config->producers()),
                    'consumer_group_keys' => array_keys($config->consumerGroups()),
                    'required_topic_keys' => $config->requiredTopicKeys(),
                ],
            ),
        );
    }
}
