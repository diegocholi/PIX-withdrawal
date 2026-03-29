<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Mapper;

use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusInput;

final class FindWithdrawStatusRequestMapper
{
    /**
     * @param array<string, mixed> $traceMetadata
     */
    public function map(
        string $accountId,
        string $withdrawId,
        string $correlationId,
        array $traceMetadata = [],
    ): FindWithdrawStatusInput {
        return new FindWithdrawStatusInput(
            accountId: trim($accountId),
            withdrawId: trim($withdrawId),
            correlationId: trim($correlationId),
            traceMetadata: $traceMetadata,
        );
    }
}
