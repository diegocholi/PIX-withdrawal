<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

interface TransactionManager
{
    public function run(callable $operation): mixed;
}
