<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Dto;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class ValidatedCreateWithdrawData implements SerializableDto
{
    private TraceContext $traceContext;

    public function __construct(
        private string $accountId,
        private string $correlationId,
        private WithdrawMethod $method,
        private PixKey $pixKey,
        private Money $amount,
        private ?ScheduleAt $scheduleAt,
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

    public function method(): WithdrawMethod
    {
        return $this->method;
    }

    public function pixKey(): PixKey
    {
        return $this->pixKey;
    }

    public function amount(): Money
    {
        return $this->amount;
    }

    public function scheduleAt(): ?ScheduleAt
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
            'method' => $this->method()->value,
            'pix_key' => $this->pixKey()->toArray(),
            'amount' => $this->amount()->toArray(),
            'schedule_at' => $this->scheduleAt()?->value()->format(DATE_ATOM),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }
}
