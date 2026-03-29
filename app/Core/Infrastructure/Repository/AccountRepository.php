<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Repository;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;

interface AccountRepository
{
    public function findById(string $accountId): ?Account;

    public function lockById(string $accountId): ?Account;

    public function save(Account $account): void;
}
