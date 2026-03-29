<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

interface AccountScopedWithdrawQuery
{
    public function find(string $accountId, string $withdrawId): ?AccountScopedWithdraw;
}
