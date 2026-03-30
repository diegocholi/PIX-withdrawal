<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageConsumer;
use Tecnofit\PixWithdrawal\Providers\Kafka\PcntlKafkaConsumerSignalListener;
use Tecnofit\PixWithdrawal\Providers\Kafka\RdkafkaConsumer;

final readonly class KafkaConsumerFactory
{
    public function __construct(
        private KafkaConfig $kafkaConfig,
        private StructuredLogger $structuredLogger,
    ) {
    }

    public function create(
        string $consumerGroupKey = 'withdraw',
        string $topicKey = KafkaConfig::TOPIC_WITHDRAW_PROCESS,
        ?string $groupIdOverride = null,
        ?string $topicOverride = null,
        ?int $pollTimeoutMsOverride = null,
        ?string $executionCorrelationId = null,
    ): KafkaMessageConsumer {
        $consumer = new KafkaConsumer($this->buildConf($consumerGroupKey, $groupIdOverride));

        return new KafkaMessageConsumer(
            new RdkafkaConsumer($consumer),
            $this->structuredLogger,
            $this->resolveTopic($topicKey, $topicOverride),
            $this->resolvePollTimeoutMs($pollTimeoutMsOverride),
            new PcntlKafkaConsumerSignalListener(),
            new KafkaConsumeFailurePolicy($this->structuredLogger),
            $executionCorrelationId,
        );
    }

    private function buildConf(string $consumerGroupKey, ?string $groupIdOverride = null): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', $this->kafkaConfig->clientId());
        $conf->set('bootstrap.servers', implode(',', $this->kafkaConfig->brokers()));
        $conf->set('group.id', $this->resolveGroupId($consumerGroupKey, $groupIdOverride));
        $conf->set('enable.auto.commit', 'false');

        foreach ($this->kafkaConfig->consumerOptions($consumerGroupKey) as $key => $value) {
            $this->applyOptionalScalarSetting($conf, $key, $value);
        }

        return $conf;
    }

    private function resolveGroupId(string $consumerGroupKey, ?string $groupIdOverride): string
    {
        return $groupIdOverride !== null
            ? $this->requireNonEmptyOverride($groupIdOverride, 'Kafka consumer group override must be non-empty.')
            : $this->kafkaConfig->consumerGroup($consumerGroupKey);
    }

    private function resolveTopic(string $topicKey, ?string $topicOverride): string
    {
        return $topicOverride !== null
            ? $this->requireNonEmptyOverride($topicOverride, 'Kafka consumer topic override must be non-empty.')
            : $this->kafkaConfig->topic($topicKey);
    }

    private function resolvePollTimeoutMs(?int $pollTimeoutMsOverride): int
    {
        if ($pollTimeoutMsOverride !== null) {
            if ($pollTimeoutMsOverride <= 0) {
                throw new \InvalidArgumentException('Kafka consumer poll timeout override must be greater than zero.');
            }

            return $pollTimeoutMsOverride;
        }

        return $this->kafkaConfig->operationTimeoutMs();
    }

    private function applyOptionalScalarSetting(Conf $conf, string $key, mixed $value): void
    {
        if (! is_scalar($value) && $value !== null) {
            throw new \InvalidArgumentException(sprintf(
                'Kafka consumer option "%s" must be scalar or null.',
                $key,
            ));
        }

        if ($value === null) {
            return;
        }

        $conf->set($key, trim((string) $value));
    }

    private function requireNonEmptyOverride(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new \InvalidArgumentException($message);
        }

        return $normalized;
    }
}
