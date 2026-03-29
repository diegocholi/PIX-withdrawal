<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Event;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class WithdrawProcessed implements DomainEvent
{
    private TraceContext $traceContext;

    public function __construct(
        private string $aggregateId,
        private string $occurredAt,
        private string $correlationId,
        private array $traceMetadata,
        private string $accountId,
        private string $amount,
        private string $method,
        private string $status,
        private ?string $pixKeyType,
        private ?string $pixKeyMasked,
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public static function fromAggregate(
        AccountWithdraw $withdraw,
        string $correlationId,
        DateTimeImmutable $occurredAt,
        ?string $pixKeyType,
        ?string $pixKeyMasked,
        array $traceMetadata = [],
    ): self {
        return new self(
            aggregateId: $withdraw->id(),
            occurredAt: $occurredAt->format(DATE_ATOM),
            correlationId: $correlationId,
            traceMetadata: $traceMetadata,
            accountId: $withdraw->accountId(),
            amount: $withdraw->amount()->toDecimal(),
            method: $withdraw->method()->value,
            status: $withdraw->status()->value,
            pixKeyType: $pixKeyType,
            pixKeyMasked: $pixKeyMasked,
        );
    }

    public function eventName(): string
    {
        return 'withdraw.processed';
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

    public function toArray(): array
    {
        return [
            'event_name' => $this->eventName(),
            'aggregate_id' => $this->aggregateId(),
            'occurred_at' => $this->occurredAt(),
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
            'payload' => [
                'withdraw_id' => $this->aggregateId(),
                'account_id' => $this->accountId,
                'amount' => $this->amount,
                'method' => $this->method,
                'status' => $this->status,
                'pix_key_type' => $this->pixKeyType,
                'pix_key_masked' => $this->pixKeyMasked,
                'error_reason' => null,
            ],
        ];
    }
}
