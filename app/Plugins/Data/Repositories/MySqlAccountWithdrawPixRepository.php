<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Repositories;

use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\WithdrawPixRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountWithdrawPixRecordMapper;

final readonly class MySqlAccountWithdrawPixRepository implements WithdrawPixRepository
{
    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountWithdrawPixRecordMapper $mapper = new AccountWithdrawPixRecordMapper(),
    ) {
    }

    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPix
    {
        $record = $this->query()
            ->where('account_withdraw_id', trim($withdrawId))
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function save(AccountWithdrawPix $withdrawPix): void
    {
        $record = $this->mapper->toRecord($withdrawPix);
        $withdrawId = (string) $record['account_withdraw_id'];
        unset($record['account_withdraw_id']);

        $this->query()->updateOrInsert(['account_withdraw_id' => $withdrawId], $record);
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account_withdraw_pix');
    }
}
