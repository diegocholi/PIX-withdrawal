<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusOutput;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;

final class FindWithdrawStatusHandler implements FindWithdrawStatus
{
    public function __construct(
        private readonly WithdrawStatusViewQuery $withdrawStatusViewQuery,
        private readonly SensitiveDataMasker $sensitiveDataMasker,
    ) {
    }

    public function execute(FindWithdrawStatusQuery $query): FindWithdrawStatusOutput
    {
        $withdrawStatusView = $this->withdrawStatusViewQuery->find(
            trim($query->accountId()),
            trim($query->withdrawId()),
        );

        if ($withdrawStatusView === null) {
            throw WithdrawNotFound::withId($query->withdrawId());
        }

        return new FindWithdrawStatusData(
            withdrawId: $withdrawStatusView->withdrawId(),
            status: $withdrawStatusView->status(),
            amount: $withdrawStatusView->amount(),
            method: $withdrawStatusView->method(),
            scheduled: $withdrawStatusView->scheduled(),
            scheduledFor: $withdrawStatusView->scheduledFor(),
            processedAt: $withdrawStatusView->processedAt(),
            errorReason: $withdrawStatusView->errorReason(),
            pixKeyType: $withdrawStatusView->pixKeyType(),
            pixKeyMasked: $this->sensitiveDataMasker->maskPixKey($withdrawStatusView->pixKeyValue()),
            correlationId: $query->correlationId(),
            traceMetadata: $query->traceMetadata(),
        );
    }
}
