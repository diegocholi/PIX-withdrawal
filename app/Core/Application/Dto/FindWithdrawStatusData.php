<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Dto;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class FindWithdrawStatusData implements FindWithdrawStatusOutput, SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $withdrawId,
        private string $status,
        private string $amount,
        private string $method,
        private bool $scheduled,
        private ?string $scheduledFor,
        private ?string $processedAt,
        private ?string $errorReason,
        private ?string $pixKeyType,
        private ?string $pixKeyMasked,
        private string $correlationId,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function withdrawId(): string
    {
        return $this->withdrawId;
    }

    public function status(): string
    {
        return $this->status;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function scheduled(): bool
    {
        return $this->scheduled;
    }

    public function scheduledFor(): ?string
    {
        return $this->scheduledFor;
    }

    public function processedAt(): ?string
    {
        return $this->processedAt;
    }

    public function errorReason(): ?string
    {
        return $this->errorReason;
    }

    public function pixKeyType(): ?string
    {
        return $this->pixKeyType;
    }

    public function pixKeyMasked(): ?string
    {
        return $this->pixKeyMasked;
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
            'withdraw_id' => $this->withdrawId(),
            'status' => $this->status(),
            'amount' => $this->amount(),
            'method' => $this->method(),
            'scheduled' => $this->scheduled(),
            'scheduled_for' => $this->scheduledFor(),
            'processed_at' => $this->processedAt(),
            'error_reason' => $this->errorReason(),
            'pix_key_type' => $this->pixKeyType(),
            'pix_key_masked' => $this->pixKeyMasked(),
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
