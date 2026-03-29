<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

final readonly class LogContext implements SerializableDto, Traceable
{
    /**
     * @var array<string, mixed>
     */
    private array $normalizedContext;
    private TraceContext $traceContext;

    /**
     * @param array<string, mixed> $traceMetadata
     * @param array<string, mixed> $context
     */
    public function __construct(
        private string $correlationId,
        private ?string $withdrawId = null,
        private ?string $accountId = null,
        private ?string $status = null,
        private ?string $errorCode = null,
        private array $traceMetadata = [],
        private array $context = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
        $this->normalizedContext = TraceContext::normalizeMetadata($this->context);
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function withdrawId(): ?string
    {
        return $this->normalizeOptional($this->withdrawId);
    }

    public function accountId(): ?string
    {
        return $this->normalizeOptional($this->accountId);
    }

    public function status(): ?string
    {
        return $this->normalizeOptional($this->status);
    }

    public function errorCode(): ?string
    {
        return $this->normalizeOptional($this->errorCode);
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->normalizedContext;
    }

    public function toArray(): array
    {
        return [
            'correlation_id' => $this->correlationId(),
            'withdraw_id' => $this->withdrawId(),
            'account_id' => $this->accountId(),
            'status' => $this->status(),
            'error_code' => $this->errorCode(),
            'trace_metadata' => $this->traceMetadata(),
            'context' => $this->context(),
        ];
    }

    private function normalizeOptional(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }
}
