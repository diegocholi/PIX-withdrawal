<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class CreateWithdrawInput implements CreateWithdrawCommand, SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $accountId,
        private string $correlationId,
        private string $method,
        private string $pixKeyType,
        private string $pixKey,
        private string $amount,
        private ?string $scheduleAt = null,
        private array $traceMetadata = [],
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public function accountId(): string
    {
        return $this->accountId;
    }

    public function correlationId(): string
    {
        return $this->traceContext->correlationId();
    }

    public function method(): string
    {
        return $this->method;
    }

    public function pixKeyType(): string
    {
        return $this->pixKeyType;
    }

    public function pixKey(): string
    {
        return $this->pixKey;
    }

    public function amount(): string
    {
        return $this->amount;
    }

    public function scheduleAt(): ?string
    {
        return $this->scheduleAt;
    }

    public function traceMetadata(): array
    {
        return $this->traceContext->traceMetadata();
    }

    public function toArray(): array
    {
        return [
            'account_id' => $this->accountId(),
            'correlation_id' => $this->correlationId(),
            'method' => $this->method(),
            'pix_key_type' => $this->pixKeyType(),
            'pix_key' => $this->pixKey(),
            'amount' => $this->amount(),
            'schedule_at' => $this->scheduleAt(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
