<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Transactions;

use Hyperf\DbConnection\Db;
use Throwable;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class MySqlTransactionManager implements TransactionManager
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function run(callable $operation): mixed
    {
        $connection = Db::connection($this->connectionConfig->name());
        $connection->beginTransaction();

        try {
            $result = $operation();
            $connection->commit();

            return $result;
        } catch (Throwable $throwable) {
            $connection->rollBack();

            throw $throwable;
        }
    }
}
