<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Queries;

use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusView;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery as WithdrawStatusViewQueryContract;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class WithdrawStatusViewQuery implements WithdrawStatusViewQueryContract
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function find(string $accountId, string $withdrawId): ?WithdrawStatusView
    {
        $record = $this->query()
            ->where('withdraw.account_id', trim($accountId))
            ->where('withdraw.id', trim($withdrawId))
            ->first();

        if ($record === null) {
            return null;
        }

        $row = (array) $record;

        return new WithdrawStatusView(
            withdrawId: trim((string) ($row['id'] ?? '')),
            status: trim((string) ($row['status'] ?? '')),
            amount: trim((string) ($row['amount'] ?? '')),
            method: trim((string) ($row['method'] ?? '')),
            scheduled: (bool) ($row['scheduled'] ?? false),
            scheduledFor: $this->toAtomString($row['scheduled_for'] ?? null),
            processedAt: $this->toAtomString($row['processed_at'] ?? null),
            errorReason: $this->toNullableString($row['error_reason'] ?? null),
            pixKeyType: $this->toNullableString($row['pix_type'] ?? null),
            pixKeyValue: $this->toNullableString($row['pix_key'] ?? null),
        );
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())
            ->table('account_withdraw as withdraw')
            ->leftJoin(
                'account_withdraw_pix as withdraw_pix',
                'withdraw_pix.account_withdraw_id',
                '=',
                'withdraw.id'
            )
            ->select([
                'withdraw.id',
                'withdraw.method',
                'withdraw.amount',
                'withdraw.scheduled',
                'withdraw.scheduled_for',
                'withdraw.status',
                'withdraw.processed_at',
                'withdraw.error_reason',
                'withdraw_pix.type as pix_type',
                'withdraw_pix.key as pix_key',
            ]);
    }

    private function toAtomString(mixed $value): ?string
    {
        $normalized = $this->toNullableString($value);

        if ($normalized === null) {
            return null;
        }

        return (new \DateTimeImmutable($normalized))->format(DATE_ATOM);
    }

    private function toNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
