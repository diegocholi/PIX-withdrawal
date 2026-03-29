<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Repositories;

use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Repository\AccountTransactionRepository;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Mappers\AccountTransactionRecordMapper;

final readonly class MySqlAccountTransactionRepository implements AccountTransactionRepository
{
    public function __construct(
        private MySqlConnectionConfig $connectionConfig,
        private AccountTransactionRecordMapper $mapper = new AccountTransactionRecordMapper(),
    ) {
    }

    public function findByWithdrawId(string $withdrawId): ?AccountTransaction
    {
        $record = $this->query()
            ->where('reference_type', AccountTransactionReferenceType::WITHDRAW->value)
            ->where('reference_id', trim($withdrawId))
            ->orderByDesc('id')
            ->first();

        return $record !== null ? $this->mapper->toDomain((array) $record) : null;
    }

    public function existsByWithdrawId(string $withdrawId): bool
    {
        return $this->query()
            ->where('reference_type', AccountTransactionReferenceType::WITHDRAW->value)
            ->where('reference_id', trim($withdrawId))
            ->exists();
    }

    public function save(AccountTransaction $accountTransaction): void
    {
        $this->query()->insert($this->mapper->toRecord($accountTransaction));
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('account_transaction');
    }
}
