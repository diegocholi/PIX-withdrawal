<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Mapper;

use InvalidArgumentException;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawAcceptedResponsePayload;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawOutput;

final class CreateWithdrawResponseMapper
{
    public function map(
        string $accountId,
        CreateWithdrawOutput $output,
        ?string $scheduledFor = null,
    ): CreateWithdrawAcceptedResponsePayload {
        $status = $this->mapStatus($output->status());
        $normalizedAccountId = trim($accountId);

        return new CreateWithdrawAcceptedResponsePayload(
            withdrawId: $output->withdrawId(),
            accountId: $normalizedAccountId,
            status: $status,
            scheduled: $status === 'scheduled',
            scheduledFor: $status === 'scheduled' ? $scheduledFor : null,
            statusUrl: sprintf('/account/%s/balance/withdraw/%s', $normalizedAccountId, $output->withdrawId()),
            correlationId: $output->correlationId(),
        );
    }

    private function mapStatus(string $status): string
    {
        return match (strtoupper(trim($status))) {
            'QUEUED' => 'queued',
            'SCHEDULED' => 'scheduled',
            default => throw new InvalidArgumentException(sprintf('Unsupported create-withdraw status "%s".', $status)),
        };
    }
}
