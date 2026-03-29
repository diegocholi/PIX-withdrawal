<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Repositories;

use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawRepository;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawRecordMapper;

final readonly class MySqlAccountWithdrawRepository implements WithdrawRepository
{
    private const STORAGE_TIMEZONE = 'UTC';

    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountWithdrawRecordMapper $mapper = new AccountWithdrawRecordMapper(),
    ) {
    }

    public function findById(string $withdrawId): ?AccountWithdraw
    {
        $record = $this->query()
            ->where('id', trim($withdrawId))
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function lockById(string $withdrawId): ?AccountWithdraw
    {
        $record = $this->query()
            ->where('id', trim($withdrawId))
            ->lockForUpdate()
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function findByIdempotencyKey(string $idempotencyKey): ?AccountWithdraw
    {
        $record = $this->query()
            ->where('idempotency_key', trim($idempotencyKey))
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function findMostRecentEquivalentSince(
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        PixKey $pixKey,
        ?ScheduleAt $scheduleAt,
        \DateTimeImmutable $since,
    ): ?AccountWithdraw {
        $normalizedSince = $this->normalizeStorageDateTime($since);
        $query = $this->query()
            ->join(
                'account_withdraw_pix',
                'account_withdraw_pix.account_withdraw_id',
                '=',
                'account_withdraw.id'
            )
            ->where('account_withdraw.account_id', trim($accountId))
            ->where('account_withdraw.method', $method->value)
            ->where('account_withdraw.amount', $amount->toDecimal())
            ->where('account_withdraw.created_at', '>=', $normalizedSince)
            ->where('account_withdraw_pix.type', $pixKey->type()->value)
            ->where('account_withdraw_pix.key', $pixKey->value());

        if ($scheduleAt === null) {
            $query->whereNull('account_withdraw.scheduled_for');
        } else {
            $query->where(
                'account_withdraw.scheduled_for',
                $this->normalizeStorageDateTime($scheduleAt->value())
            );
        }

        $record = $query
            ->select('account_withdraw.*')
            ->orderByDesc('account_withdraw.created_at')
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function save(AccountWithdraw $withdraw): void
    {
        $record = $this->mapper->toRecord($withdraw);
        $withdrawId = (string) $record['id'];
        $persistedRecord = $record;
        unset($record['id']);

        if (! $this->exists($withdrawId)) {
            $this->query()->insert($persistedRecord);

            return;
        }

        $allowedCurrentStatuses = $this->allowedCurrentStatusesFor($withdraw->status());

        if ($allowedCurrentStatuses === null) {
            $this->query()
                ->where('id', $withdrawId)
                ->update($record);

            return;
        }

        $this->query()
            ->where('id', $withdrawId)
            ->whereIn('status', array_map(static fn (WithdrawStatus $status): string => $status->value, $allowedCurrentStatuses))
            ->whereNull('processed_at')
            ->update($record);
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account_withdraw');
    }

    private function normalizeStorageDateTime(\DateTimeImmutable $value): string
    {
        return $value
            ->setTimezone(new \DateTimeZone(self::STORAGE_TIMEZONE))
            ->format('Y-m-d H:i:s.u');
    }

    private function exists(string $withdrawId): bool
    {
        return $this->query()
            ->where('id', $withdrawId)
            ->exists();
    }

    /**
     * @return list<WithdrawStatus>|null
     */
    private function allowedCurrentStatusesFor(WithdrawStatus $targetStatus): ?array
    {
        return match ($targetStatus) {
            WithdrawStatus::SCHEDULED => [WithdrawStatus::PENDING],
            WithdrawStatus::QUEUED => [WithdrawStatus::PENDING, WithdrawStatus::SCHEDULED],
            WithdrawStatus::PROCESSING => [WithdrawStatus::QUEUED],
            WithdrawStatus::DONE,
            WithdrawStatus::FAILED_INSUFFICIENT_FUNDS,
            WithdrawStatus::FAILED_INTERNAL => [WithdrawStatus::PROCESSING],
            default => null,
        };
    }
}
