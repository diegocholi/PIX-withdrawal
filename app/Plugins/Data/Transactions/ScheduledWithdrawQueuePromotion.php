<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Transactions;

use DateTimeImmutable;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion as ScheduledWithdrawQueuePromotionContract;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class ScheduledWithdrawQueuePromotion implements ScheduledWithdrawQueuePromotionContract
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function promote(string $withdrawId, DateTimeImmutable $queuedAt): bool
    {
        $timestamp = $queuedAt->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');

        $affectedRows = $this->query()
            ->where('id', trim($withdrawId))
            ->where('status', WithdrawStatus::SCHEDULED->value)
            ->where('scheduled_for', '<=', $timestamp)
            ->whereNull('queued_at')
            ->whereNull('processing_started_at')
            ->whereNull('processed_at')
            ->update([
                'status' => WithdrawStatus::QUEUED->value,
                'queued_at' => $timestamp,
                'updated_at' => $timestamp,
            ]);

        return $affectedRows === 1;
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account_withdraw');
    }
}
