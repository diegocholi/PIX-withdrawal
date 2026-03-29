<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Event;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Shared\TraceContext;

final readonly class WithdrawQueued implements DomainEvent
{
    private TraceContext $traceContext;

    public function __construct(
        private string $aggregateId,
        private string $occurredAt,
        private string $correlationId,
        private array $traceMetadata,
        private string $accountId,
        private string $method,
        private string $status,
        private string $amount,
        private ?string $pixKeyType,
        private ?string $queuedAt,
        private ?string $scheduledAt,
    ) {
        $this->traceContext = new TraceContext($this->correlationId, $this->traceMetadata);
    }

    public static function fromAggregate(
        AccountWithdraw $withdraw,
        ?AccountWithdrawPix $withdrawPix,
        DateTimeImmutable $occurredAt,
        array $traceMetadata = [],
    ): self {
        return new self(
            aggregateId: $withdraw->id(),
            occurredAt: $occurredAt->format(DATE_ATOM),
            correlationId: $withdraw->correlationId(),
            traceMetadata: $traceMetadata,
            accountId: $withdraw->accountId(),
            method: $withdraw->method()->value,
            status: $withdraw->status()->value,
            amount: $withdraw->amount()->toDecimal(),
            pixKeyType: $withdrawPix?->pixKeyType()->value,
            queuedAt: $withdraw->queuedAt()?->format(DATE_ATOM),
            scheduledAt: $withdraw->scheduledFor()?->value()->format(DATE_ATOM),
        );
    }

    public function eventName(): string
    {
        return 'withdraw.queued';
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
                'method' => $this->method,
                'status' => $this->status,
                'amount' => $this->amount,
                'pix_key_type' => $this->pixKeyType,
                'queued_at' => $this->queuedAt,
                'scheduled_at' => $this->scheduledAt,
            ],
        ];
    }
}
