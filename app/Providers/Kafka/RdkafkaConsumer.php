<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\TopicPartition;

final class RdkafkaConsumer implements KafkaConsumerTransport
{
    /**
     * @var list<string>
     */
    private array $subscribedTopics = [];

    public function __construct(private readonly KafkaConsumer $consumer)
    {
    }

    public function subscribe(array $topics): void
    {
        $this->subscribedTopics = array_values(
            array_filter(
                array_map(static fn (string $topic): string => trim($topic), $topics),
                static fn (string $topic): bool => $topic !== '',
            ),
        );

        if ($this->subscribedTopics === []) {
            throw new \InvalidArgumentException('Kafka consumer must subscribe to at least one topic.');
        }

        $this->consumer->subscribe($this->subscribedTopics);
    }

    public function consume(int $timeoutMs): ?KafkaConsumerMessage
    {
        $message = $this->consumer->consume(max(0, $timeoutMs));

        if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
            return $this->toConsumerMessage($message);
        }

        if (in_array($message->err, [RD_KAFKA_RESP_ERR__PARTITION_EOF, RD_KAFKA_RESP_ERR__TIMED_OUT], true)) {
            return null;
        }

        throw KafkaConsumeException::pollingFailed(
            $this->subscribedTopics,
            $message->err,
            $message->errstr(),
        );
    }

    public function commit(KafkaConsumerMessage $message): void
    {
        try {
            $this->consumer->commit([
                new TopicPartition(
                    $message->topic(),
                    $message->partition(),
                    $message->offset() + 1,
                ),
            ]);
        } catch (\Throwable $throwable) {
            throw KafkaConsumeException::commitFailed($message, $throwable);
        }
    }

    private function toConsumerMessage(Message $message): KafkaConsumerMessage
    {
        return new KafkaConsumerMessage(
            topic: trim((string) $message->topic_name),
            payload: trim((string) $message->payload),
            key: $message->key === null ? null : trim((string) $message->key),
            headers: $this->normalizeHeaders($message->headers ?? []),
            partition: (int) $message->partition,
            offset: (int) $message->offset,
        );
    }

    /**
     * @param array<string, mixed> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $value) {
            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $normalizedKey = trim((string) $key);
            $normalizedValue = trim((string) $value);

            if ($normalizedKey === '' || $normalizedValue === '') {
                continue;
            }

            $normalized[$normalizedKey] = $normalizedValue;
        }

        return $normalized;
    }
}
