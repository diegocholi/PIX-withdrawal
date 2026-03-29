<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka\Payload;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class KafkaEventPayload implements SerializableDto
{
    /**
     * @param array<string, mixed> $traceMetadata
     */
    public function __construct(
        private string $eventName,
        private string $aggregateId,
        private string $occurredAt,
        private string $correlationId,
        private array $traceMetadata,
        private SerializableDto $payload,
    ) {
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName,
            'aggregate_id' => $this->aggregateId,
            'occurred_at' => $this->occurredAt,
            'correlation_id' => $this->correlationId,
            'trace_metadata' => $this->traceMetadata,
            'payload' => $this->payload->toArray(),
        ];
    }
}
