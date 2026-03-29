<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Queries;

use DateTimeImmutable;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery as DueScheduledWithdrawQueryContract;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawRecordMapper;

final readonly class DueScheduledWithdrawQuery implements DueScheduledWithdrawQueryContract
{
    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountWithdrawRecordMapper $withdrawMapper = new AccountWithdrawRecordMapper(),
    ) {
    }

    public function findDue(DateTimeImmutable $scheduledUntil, int $limit): array
    {
        if ($limit <= 0) {
            return [];
        }

        $records = $this->query()
            ->where('status', WithdrawStatus::SCHEDULED->value)
            ->where('scheduled_for', '<=', $scheduledUntil->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u'))
            ->orderBy('scheduled_for')
            ->orderBy('created_at')
            ->limit($limit)
            ->get();

        return array_map(
            fn (object $record): AccountWithdraw => $this->withdrawMapper->toDomain((array) $record),
            $records->all(),
        );
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account_withdraw');
    }
}
