<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Queries;

use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdraw;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdrawQuery as AccountScopedWithdrawQueryContract;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawPixRecordMapper;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawRecordMapper;

final readonly class AccountScopedWithdrawQuery implements AccountScopedWithdrawQueryContract
{
    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountWithdrawRecordMapper $withdrawMapper = new AccountWithdrawRecordMapper(),
        private AccountWithdrawPixRecordMapper $withdrawPixMapper = new AccountWithdrawPixRecordMapper(),
    ) {
    }

    public function find(string $accountId, string $withdrawId): ?AccountScopedWithdraw
    {
        $record = $this->query()
            ->where('withdraw.account_id', trim($accountId))
            ->where('withdraw.id', trim($withdrawId))
            ->first();

        if ($record === null) {
            return null;
        }

        $row = (array) $record;

        return new AccountScopedWithdraw(
            withdraw: $this->withdrawMapper->toDomain($row),
            withdrawPix: $this->toWithdrawPix($row),
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
                'withdraw.account_id',
                'withdraw.method',
                'withdraw.amount',
                'withdraw.scheduled',
                'withdraw.scheduled_for',
                'withdraw.status',
                'withdraw.error_reason',
                'withdraw.requested_at',
                'withdraw.queued_at',
                'withdraw.processing_started_at',
                'withdraw.processed_at',
                'withdraw.correlation_id',
                'withdraw.idempotency_key',
                'withdraw.retry_count',
                'withdraw.last_retry_at',
                'withdraw.created_at',
                'withdraw.updated_at',
                'withdraw_pix.account_withdraw_id as pix_account_withdraw_id',
                'withdraw_pix.type as pix_type',
                'withdraw_pix.key as pix_key',
                'withdraw_pix.created_at as pix_created_at',
                'withdraw_pix.updated_at as pix_updated_at',
            ]);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function toWithdrawPix(array $row): ?\Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix
    {
        $withdrawId = trim((string) ($row['pix_account_withdraw_id'] ?? ''));

        if ($withdrawId === '') {
            return null;
        }

        return $this->withdrawPixMapper->toDomain([
            'account_withdraw_id' => $withdrawId,
            'type' => $row['pix_type'] ?? null,
            'key' => $row['pix_key'] ?? null,
            'created_at' => $row['pix_created_at'] ?? null,
            'updated_at' => $row['pix_updated_at'] ?? null,
        ]);
    }
}
