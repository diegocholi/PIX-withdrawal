<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class PublishDomainEventInput implements PublishDomainEventCommand, SerializableDto
{
    private TraceContext $traceContext;

    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private string $eventName,
        private array $payload,
        private string $correlationId,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function payload(): array
    {
        return $this->payload;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'payload' => $this->payload(),
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
