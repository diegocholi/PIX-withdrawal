<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Dto;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class PublishDomainEventData implements PublishDomainEventOutput, SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $eventName,
        private string $correlationId,
        private bool $published,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function eventName(): string
    {
        return $this->eventName;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function published(): bool
    {
        return $this->published;
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'correlation_id' => $this->correlationId(),
            'published' => $this->published(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
