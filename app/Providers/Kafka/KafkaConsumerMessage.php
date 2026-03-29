<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final readonly class KafkaConsumerMessage
{
    private string $topic;
    private string $payload;
    private ?string $key;
    /**
     * @var array<string, string>
     */
    private array $headers;
    /**
     * @var array<string, mixed>
     */
    private array $payloadData;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $topic,
        string $payload,
        ?string $key,
        array $headers,
        private int $partition,
        private int $offset,
    ) {
        $this->topic = $this->normalizeRequired($topic, 'Kafka consumer topic must be non-empty.');
        $this->payload = $this->normalizeRequired($payload, 'Kafka consumer payload must be non-empty.');
        $this->key = $this->normalizeOptional($key);
        $this->headers = $this->normalizeHeaders($headers);
        $this->payloadData = $this->decodePayload($this->payload);
        $this->assertRequiredMetadata('correlation_id');
        $this->assertRequiredMetadata('event_name');
        $this->assertRequiredMetadata('occurred_at');
    }

    public function topic(): string
    {
        return $this->topic;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * @return array<string, string>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @return array<string, mixed>
     */
    public function payloadData(): array
    {
        return $this->payloadData;
    }

    public function partition(): int
    {
        return $this->partition;
    }

    public function offset(): int
    {
        return $this->offset;
    }

    public function correlationId(): string
    {
        $correlationId = $this->metadata('correlation_id');

        return $correlationId ?? 'kafka-consumer';
    }

    public function eventName(): string
    {
        return $this->metadata('event_name') ?? '';
    }

    public function occurredAt(): string
    {
        return $this->metadata('occurred_at') ?? '';
    }

    private function normalizeRequired(string $value, string $message): string
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new \InvalidArgumentException($message);
        }

        return $normalized;
    }

    private function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function normalizeHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $key => $value) {
            $headerKey = trim($key);
            $headerValue = trim($value);

            if ($headerKey === '') {
                throw new \InvalidArgumentException('Kafka consumer header name must be non-empty.');
            }

            if ($headerValue === '') {
                throw new \InvalidArgumentException(sprintf('Kafka consumer header "%s" must be non-empty.', $headerKey));
            }

            $normalized[$headerKey] = $headerValue;
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePayload(string $payload): array
    {
        try {
            $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidKafkaMessage::invalidJsonPayload($exception);
        }

        if (! is_array($decodedPayload) || array_is_list($decodedPayload)) {
            throw InvalidKafkaMessage::invalidPayloadShape();
        }

        /** @var array<string, mixed> $decodedPayload */
        return $decodedPayload;
    }

    private function assertRequiredMetadata(string $field): void
    {
        if ($this->metadata($field) === null) {
            throw InvalidKafkaMessage::missingRequiredMetadata($field);
        }
    }

    private function metadata(string $field): ?string
    {
        $headerValue = $this->normalizeOptional($this->headers[$field] ?? null);

        if ($headerValue !== null) {
            return $headerValue;
        }

        $payloadValue = $this->payloadData[$field] ?? null;

        if (! is_scalar($payloadValue) && $payloadValue !== null) {
            return null;
        }

        return $this->normalizeOptional($payloadValue === null ? null : (string) $payloadValue);
    }
}
