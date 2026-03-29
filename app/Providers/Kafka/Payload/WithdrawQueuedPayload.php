<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka\Payload;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class WithdrawQueuedPayload implements SerializableDto
{
    public function __construct(
        private string $withdrawId,
        private string $accountId,
        private string $method,
        private string $status,
        private string $amount,
        private ?string $pixKeyType,
        private ?string $queuedAt,
        private ?string $scheduledAt,
    ) {
    }

    public function toArray(): array
    {
        return [
            'withdraw_id' => $this->withdrawId,
            'account_id' => $this->accountId,
            'method' => $this->method,
            'status' => $this->status,
            'amount' => $this->amount,
            'pix_key_type' => $this->pixKeyType,
            'queued_at' => $this->queuedAt,
            'scheduled_at' => $this->scheduledAt,
        ];
    }
}
