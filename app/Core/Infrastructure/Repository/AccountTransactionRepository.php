<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Repository;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;

interface AccountTransactionRepository
{
    public function findByWithdrawId(string $withdrawId): ?AccountTransaction;

    public function existsByWithdrawId(string $withdrawId): bool;

    public function save(AccountTransaction $accountTransaction): void;
}
