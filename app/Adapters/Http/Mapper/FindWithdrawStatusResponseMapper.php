<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Mapper;

use InvalidArgumentException;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\FindWithdrawStatusPixResponsePayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\FindWithdrawStatusResponsePayload;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusOutput;

final class FindWithdrawStatusResponseMapper
{
    public function map(
        string $accountId,
        FindWithdrawStatusOutput $output,
    ): FindWithdrawStatusResponsePayload {
        [$status, $failureCategory] = $this->mapStatus($output->status());

        return new FindWithdrawStatusResponsePayload(
            withdrawId: $output->withdrawId(),
            accountId: trim($accountId),
            status: $status,
            amount: $output->amount(),
            method: $output->method(),
            pix: $this->mapPixPayload($output),
            scheduled: $output->scheduled(),
            scheduledFor: $output->scheduledFor(),
            processedAt: $output->processedAt(),
            errorReason: $output->errorReason(),
            failureCategory: $failureCategory,
            correlationId: $output->correlationId(),
        );
    }

    private function mapPixPayload(FindWithdrawStatusOutput $output): ?FindWithdrawStatusPixResponsePayload
    {
        $pixKeyType = $output->pixKeyType();
        $pixKeyMasked = $output->pixKeyMasked();

        if ($pixKeyType === null || $pixKeyMasked === null) {
            return null;
        }

        return new FindWithdrawStatusPixResponsePayload(
            keyType: $pixKeyType,
            keyMasked: $pixKeyMasked,
        );
    }

    /**
     * @return array{0: string, 1: null|string}
     */
    private function mapStatus(string $status): array
    {
        return match (strtoupper(trim($status))) {
            'PENDING' => ['pending', null],
            'SCHEDULED' => ['scheduled', null],
            'QUEUED' => ['queued', null],
            'PROCESSING' => ['processing', null],
            'DONE' => ['completed', null],
            'FAILED_INSUFFICIENT_FUNDS' => ['failed', 'insufficient_funds'],
            'FAILED_VALIDATION' => ['failed', 'validation'],
            'FAILED_INTERNAL' => ['failed', 'internal'],
            default => throw new InvalidArgumentException(sprintf('Unsupported withdraw status "%s".', $status)),
        };
    }
}
