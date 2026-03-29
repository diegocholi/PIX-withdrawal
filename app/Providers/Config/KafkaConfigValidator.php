<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

final readonly class KafkaConfigValidator
{
    public function __construct(private ProviderConfigProvider $providerConfigProvider)
    {
    }

    public function validate(KafkaConfig $config): void
    {
        if (trim($config->clientId()) === '') {
            throw new \InvalidArgumentException('Kafka client_id configuration must be non-empty.');
        }

        if ($this->providerConfigProvider->providerEnvironment() === ProviderEnvironment::TEST) {
            return;
        }

        if ($config->brokers() === []) {
            throw new \InvalidArgumentException('Kafka brokers configuration must define at least one broker outside test environment.');
        }

        $missingRequiredTopics = array_values(
            array_filter(
                $config->requiredTopicKeys(),
                static fn (string $topicKey): bool => ! array_key_exists($topicKey, $config->topics())
                    || trim((string) $config->topics()[$topicKey]) === ''
            )
        );

        if ($missingRequiredTopics !== []) {
            throw new \InvalidArgumentException(sprintf(
                'Kafka topics configuration must define required topics: %s.',
                implode(', ', $missingRequiredTopics)
            ));
        }
    }
}
