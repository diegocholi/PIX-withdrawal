<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use RdKafka\Conf;
use RdKafka\Producer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Kafka\RdkafkaProducer;

final readonly class KafkaProducerFactory
{
    public function __construct(
        private KafkaConfig $kafkaConfig,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function create(string $producerKey = 'default'): KafkaMessageProducer
    {
        $configuration = $this->producerConfiguration($producerKey);
        $producer = new Producer($this->buildConf($configuration));

        return new KafkaMessageProducer(
            new RdkafkaProducer($producer, $this->kafkaConfig->flushTimeoutMs()),
            $this->structuredLogger,
            new KafkaPublishFailurePolicy($this->structuredLogger),
        );
    }

    /**
     * @return array<string, scalar|null>
     */
    private function producerConfiguration(string $producerKey): array
    {
        if ($producerKey === 'default') {
            return [];
        }

        $config = $this->kafkaConfig->producers()[$producerKey] ?? null;

        if (! is_array($config)) {
            throw new \InvalidArgumentException(sprintf('Kafka producer "%s" is not configured.', $producerKey));
        }

        return $config;
    }

    /**
     * @param array<string, scalar|null> $configuration
     */
    private function buildConf(array $configuration): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', $this->kafkaConfig->clientId());
        $conf->set('bootstrap.servers', implode(',', $this->kafkaConfig->brokers()));

        foreach ($configuration as $key => $value) {
            $this->applyOptionalScalarSetting($conf, $key, $value);
        }

        return $conf;
    }

    private function applyOptionalScalarSetting(Conf $conf, string $key, mixed $value): void
    {
        if (! is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException(sprintf(
                'Kafka producer option "%s" must be scalar or null.',
                $key,
            ));
        }

        if ($value === null) {
            return;
        }

        $conf->set($key, trim((string) $value));
    }
}
