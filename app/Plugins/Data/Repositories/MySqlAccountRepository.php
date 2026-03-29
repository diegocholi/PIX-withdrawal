<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Repositories;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountRecordMapper;

final readonly class MySqlAccountRepository implements AccountRepository
{
    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountRecordMapper $mapper = new AccountRecordMapper(),
    ) {
    }

    public function findById(string $accountId): ?Account
    {
        $record = $this->query()
            ->where('id', trim($accountId))
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function lockById(string $accountId): ?Account
    {
        $record = $this->query()
            ->where('id', trim($accountId))
            ->lockForUpdate()
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function save(Account $account): void
    {
        $record = $this->mapper->toRecord($account);
        $accountId = (string) $record['id'];
        unset($record['id']);

        $this->query()->updateOrInsert(['id' => $accountId], $record);
    }

    private function query(): \Hyperf\Database\Query\Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account');
    }
}
