<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class ProcessWithdrawInput implements ProcessWithdrawCommand, SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $withdrawId,
        private string $correlationId,
        private int $attempt = 1,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function withdrawId(): string
    {
        return $this->withdrawId;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function attempt(): int
    {
        return $this->attempt;
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function toArray(): array
    {
        return [
            'withdraw_id' => $this->withdrawId(),
            'correlation_id' => $this->correlationId(),
            'attempt' => $this->attempt(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
