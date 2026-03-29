<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka\Payload;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class WithdrawProcessedPayload implements SerializableDto
{
    public function __construct(
        private string $withdrawId,
        private string $accountId,
        private string $amount,
        private string $method,
        private string $status,
        private ?string $pixKeyType,
        private ?string $pixKeyMasked,
        private ?string $errorReason,
    ) {
    }

    public function toArray(): array
    {
        return [
            'withdraw_id' => $this->withdrawId,
            'account_id' => $this->accountId,
            'amount' => $this->amount,
            'method' => $this->method,
            'status' => $this->status,
            'pix_key_type' => $this->pixKeyType,
            'pix_key_masked' => $this->pixKeyMasked,
            'error_reason' => $this->errorReason,
        ];
    }
}
