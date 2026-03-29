<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class FindWithdrawStatusResponsePayload
{
    public function __construct(
        private string $withdrawId,
        private string $accountId,
        private string $status,
        private string $amount,
        private string $method,
        private ?FindWithdrawStatusPixResponsePayload $pix,
        private bool $scheduled,
        private ?string $scheduledFor,
        private ?string $processedAt,
        private ?string $errorReason,
        private ?string $failureCategory,
        private string $correlationId,
    ) {
    }

    public function toArray(): array
    {
        return [
            'data' => [
                'withdraw_id' => $this->withdrawId,
                'account_id' => $this->accountId,
                'status' => $this->status,
                'amount' => $this->amount,
                'method' => $this->method,
                'pix' => $this->pix?->toArray(),
                'scheduled' => $this->scheduled,
                'scheduled_for' => $this->scheduledFor,
                'processed_at' => $this->processedAt,
                'error_reason' => $this->errorReason,
                'failure_category' => $this->failureCategory,
            ],
            'meta' => [
                'correlation_id' => $this->correlationId,
            ],
        ];
    }
}
