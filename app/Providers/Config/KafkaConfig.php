<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

final readonly class KafkaConfig
{
    public const TOPIC_WITHDRAW_PROCESS = 'withdraw_process';
    public const TOPIC_WITHDRAW_SUCCEEDED = 'withdraw_succeeded';
    public const TOPIC_WITHDRAW_FAILED = 'withdraw_failed';
    public const TOPIC_NOTIFICATION_EMAIL_WITHDRAW = 'notification_email_withdraw';

    /**
     * @param list<string> $brokers
     * @param list<string> $requiredTopicKeys
     * @param array<string, mixed> $consumerGroups
     * @param array<string, mixed> $producers
     * @param array<string, mixed> $topics
     */
    public function __construct(
        private array $brokers,
        private string $clientId,
        private array $requiredTopicKeys,
        private array $consumerGroups,
        private array $producers,
        private array $topics,
        private int $operationTimeoutMs,
        private int $flushTimeoutMs,
    ) {
    }

    /**
     * @param array<string, mixed> $config
     */
    public static function fromArray(array $config): self
    {
        $brokers = array_values(
            array_filter(
                array_map(
                    static fn (mixed $broker): string => trim((string) $broker),
                    is_array($config['brokers'] ?? null) ? $config['brokers'] : []
                ),
                static fn (string $broker): bool => $broker !== ''
            )
        );

        return new self(
            brokers: $brokers,
            clientId: trim((string) ($config['client_id'] ?? '')),
            requiredTopicKeys: array_values(
                array_filter(
                    array_map(
                        static fn (mixed $topicKey): string => trim((string) $topicKey),
                        is_array($config['required_topic_keys'] ?? null) ? $config['required_topic_keys'] : []
                    ),
                    static fn (string $topicKey): bool => $topicKey !== ''
                )
            ),
            consumerGroups: is_array($config['consumer_groups'] ?? null) ? $config['consumer_groups'] : [],
            producers: is_array($config['producers'] ?? null) ? $config['producers'] : [],
            topics: self::normalizeTopics(is_array($config['topics'] ?? null) ? $config['topics'] : []),
            operationTimeoutMs: max(0, (int) ($config['operation_timeout_ms'] ?? 1000)),
            flushTimeoutMs: max(0, (int) ($config['flush_timeout_ms'] ?? 5000)),
        );
    }

    /**
     * @return list<string>
     */
    public function brokers(): array
    {
        return $this->brokers;
    }

    public function clientId(): string
    {
        return $this->clientId;
    }

    /**
     * @return list<string>
     */
    public function requiredTopicKeys(): array
    {
        return $this->requiredTopicKeys;
    }

    /**
     * @return array<string, mixed>
     */
    public function consumerGroups(): array
    {
        return $this->consumerGroups;
    }

    public function consumerGroup(string $groupKey): string
    {
        $definition = $this->consumerGroupDefinition($groupKey);

        return trim((string) ($definition['group_id'] ?? ''));
    }

    /**
     * @return array<string, scalar|null>
     */
    public function consumerOptions(string $groupKey): array
    {
        $definition = $this->consumerGroupDefinition($groupKey);
        unset($definition['group_id']);

        /** @var array<string, scalar|null> $definition */
        return $definition;
    }

    /**
     * @return array<string, mixed>
     */
    public function producers(): array
    {
        return $this->producers;
    }

    /**
     * @return array<string, mixed>
     */
    public function topics(): array
    {
        return $this->topics;
    }

    public function topic(string $topicKey): string
    {
        $topicName = trim((string) ($this->topics[$topicKey] ?? ''));

        if ($topicName === '') {
            throw new \InvalidArgumentException(sprintf('Kafka topic "%s" is not configured.', $topicKey));
        }

        return $topicName;
    }

    public function withdrawProcessTopic(): string
    {
        return $this->topic(self::TOPIC_WITHDRAW_PROCESS);
    }

    public function withdrawSucceededTopic(): string
    {
        return $this->topic(self::TOPIC_WITHDRAW_SUCCEEDED);
    }

    public function withdrawFailedTopic(): string
    {
        return $this->topic(self::TOPIC_WITHDRAW_FAILED);
    }

    public function notificationEmailWithdrawTopic(): string
    {
        return $this->topic(self::TOPIC_NOTIFICATION_EMAIL_WITHDRAW);
    }

    public function operationTimeoutMs(): int
    {
        return $this->operationTimeoutMs;
    }

    public function flushTimeoutMs(): int
    {
        return $this->flushTimeoutMs;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'brokers' => $this->brokers,
            'client_id' => $this->clientId,
            'required_topic_keys' => $this->requiredTopicKeys,
            'consumer_groups' => $this->consumerGroups,
            'producers' => $this->producers,
            'topics' => $this->topics,
            'operation_timeout_ms' => $this->operationTimeoutMs,
            'flush_timeout_ms' => $this->flushTimeoutMs,
        ];
    }

    /**
     * @param array<string, mixed> $topics
     * @return array<string, string>
     */
    private static function normalizeTopics(array $topics): array
    {
        $normalized = [];

        foreach ($topics as $topicKey => $topicName) {
            $normalizedKey = trim((string) $topicKey);
            $normalizedName = trim((string) $topicName);

            if ($normalizedKey === '' || $normalizedName === '') {
                continue;
            }

            $normalized[$normalizedKey] = $normalizedName;
        }

        return $normalized;
    }

    /**
     * @return array<string, scalar|null>
     */
    private function consumerGroupDefinition(string $groupKey): array
    {
        $definition = $this->consumerGroups[$groupKey] ?? null;

        if (is_string($definition)) {
            $groupId = trim($definition);

            if ($groupId === '') {
                throw new \InvalidArgumentException(sprintf('Kafka consumer group "%s" is not configured.', $groupKey));
            }

            return ['group_id' => $groupId];
        }

        if (! is_array($definition)) {
            throw new \InvalidArgumentException(sprintf('Kafka consumer group "%s" is not configured.', $groupKey));
        }

        $normalized = [];

        foreach ($definition as $key => $value) {
            $normalizedKey = trim((string) $key);

            if ($normalizedKey === '') {
                continue;
            }

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $normalized[$normalizedKey] = $value === null ? null : trim((string) $value);
        }

        $groupId = trim((string) ($normalized['group_id'] ?? ''));

        if ($groupId === '') {
            throw new \InvalidArgumentException(sprintf('Kafka consumer group "%s" must define a non-empty group_id.', $groupKey));
        }

        /** @var array<string, scalar|null> $normalized */
        return $normalized;
    }
}
