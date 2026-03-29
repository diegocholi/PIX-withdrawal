<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final readonly class KafkaProducerRecord
{
    private const REQUIRED_OPERATIONAL_HEADERS = [
        'correlation_id',
        'event_name',
        'occurred_at',
    ];

    private string $topic;
    private string $payload;
    private ?string $key;
    /**
     * @var array<string, string>
     */
    private array $headers;
    private int $partition;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        string $topic,
        string $payload,
        ?string $key = null,
        array $headers = [],
        int $partition = RD_KAFKA_PARTITION_UA,
    ) {
        $this->topic = $this->normalizeRequired($topic, 'Kafka topic must be non-empty.');
        $this->payload = $this->normalizeRequired($payload, 'Kafka payload must be non-empty.');
        $this->key = $this->normalizeOptional($key) ?? $this->keyFromPayload($this->payload);
        $this->headers = $this->normalizeHeaders($this->payload, $headers);
        $this->partition = $partition;
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

    public function partition(): int
    {
        return $this->partition;
    }

    public function correlationId(): string
    {
        return $this->headers['correlation_id'];
    }

    public function eventName(): string
    {
        return $this->headers['event_name'];
    }

    public function occurredAt(): string
    {
        return $this->headers['occurred_at'];
    }

    /**
     * @return array<string, mixed>
     */
    public function traceMetadata(): array
    {
        $decodedPayload = $this->decodePayload($this->payload);

        if (! is_array($decodedPayload['trace_metadata'] ?? null)) {
            return [];
        }

        /** @var array<string, mixed> $traceMetadata */
        $traceMetadata = $decodedPayload['trace_metadata'];

        return $traceMetadata;
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
    private function normalizeHeaders(string $payload, array $headers): array
    {
        $normalized = $this->headersFromPayload($payload);

        foreach ($headers as $key => $value) {
            $headerKey = trim($key);
            $headerValue = trim($value);

            if ($headerKey === '') {
                throw new \InvalidArgumentException('Kafka header name must be non-empty.');
            }

            if ($headerValue === '') {
                throw new \InvalidArgumentException(sprintf('Kafka header "%s" must be non-empty.', $headerKey));
            }

            $normalized[$headerKey] = $headerValue;
        }

        foreach (self::REQUIRED_OPERATIONAL_HEADERS as $requiredHeader) {
            if (! array_key_exists($requiredHeader, $normalized)) {
                throw new \InvalidArgumentException(sprintf(
                    'Kafka header "%s" is required for published messages.',
                    $requiredHeader,
                ));
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, string>
     */
    private function headersFromPayload(string $payload): array
    {
        $decodedPayload = $this->decodePayload($payload);

        if ($decodedPayload === null) {
            return [];
        }

        $headers = [];

        foreach (self::REQUIRED_OPERATIONAL_HEADERS as $headerKey) {
            $value = $decodedPayload[$headerKey] ?? null;

            if (! is_scalar($value) && $value !== null) {
                continue;
            }

            $normalizedValue = trim((string) $value);

            if ($normalizedValue === '') {
                continue;
            }

            $headers[$headerKey] = $normalizedValue;
        }

        return $headers;
    }

    private function keyFromPayload(string $payload): ?string
    {
        $decodedPayload = $this->decodePayload($payload);

        if ($decodedPayload === null) {
            return null;
        }

        $nestedPayload = $decodedPayload['payload'] ?? null;

        if (is_array($nestedPayload)) {
            $accountId = $this->normalizeOptional($this->scalarToString($nestedPayload['account_id'] ?? null));

            if ($accountId !== null) {
                return $accountId;
            }
        }

        return $this->normalizeOptional($this->scalarToString($decodedPayload['account_id'] ?? null));
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodePayload(string $payload): ?array
    {
        try {
            $decodedPayload = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return is_array($decodedPayload) ? $decodedPayload : null;
    }

    private function scalarToString(mixed $value): ?string
    {
        if (! is_scalar($value) && $value !== null) {
            return null;
        }

        return $value === null ? null : (string) $value;
    }
}
