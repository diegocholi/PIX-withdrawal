<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Mapper;

use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput;

final class CreateWithdrawRequestMapper
{
    /**
     * @param array<string, mixed> $traceMetadata
     */
    public function map(
        string $accountId,
        string $correlationId,
        CreateWithdrawRequestPayload $payload,
        array $traceMetadata = [],
    ): CreateWithdrawInput {
        $requestPayload = $payload->toArray();
        $pixPayload = $requestPayload['pix'];
        $schedulePayload = $requestPayload['schedule'];

        return new CreateWithdrawInput(
            accountId: trim($accountId),
            correlationId: trim($correlationId),
            method: (string) $requestPayload['method'],
            pixKeyType: (string) $pixPayload['key_type'],
            pixKey: (string) $pixPayload['key'],
            amount: (string) $requestPayload['amount'],
            scheduleAt: is_array($schedulePayload) ? (string) ($schedulePayload['at'] ?? '') : null,
            traceMetadata: $traceMetadata,
        );
    }
}
