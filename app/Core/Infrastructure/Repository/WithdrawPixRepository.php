<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Repository;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;

interface WithdrawPixRepository
{
    public function findByWithdrawId(string $withdrawId): ?AccountWithdrawPix;

    public function save(AccountWithdrawPix $withdrawPix): void;
}
