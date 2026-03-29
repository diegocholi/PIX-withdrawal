<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

final readonly class WithdrawStatusView
{
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
        private ?string $pixKeyValue,
    ) {
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

    public function pixKeyValue(): ?string
    {
        return $this->pixKeyValue;
    }
}
