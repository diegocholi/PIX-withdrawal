<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Event;

use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class GenericDomainEvent implements DomainEvent
{
    private TraceContext $traceContext;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $eventName,
        private string $aggregateId,
        private string $occurredAt,
        private string $correlationId,
        private array $traceMetadata = [],
        private array $payload = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function aggregateId(): string
    {
        return $this->aggregateId;
    }

    public function occurredAt(): string
    {
        return $this->occurredAt;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'aggregate_id' => $this->aggregateId(),
            'occurred_at' => $this->occurredAt(),
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
            'payload' => $this->payload(),
        ];
    }
}
