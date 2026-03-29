<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class CreateWithdrawAcceptedResponsePayload
{
    public function __construct(
        private string $withdrawId,
        private string $accountId,
        private string $status,
        private bool $scheduled,
        private ?string $scheduledFor,
        private string $statusUrl,
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
                'scheduled' => $this->scheduled,
                'scheduled_for' => $this->scheduledFor,
                'status_url' => $this->statusUrl,
            ],
            'meta' => [
                'correlation_id' => $this->correlationId,
            ],
        ];
    }
}
