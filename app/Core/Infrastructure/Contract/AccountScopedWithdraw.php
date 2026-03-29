<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;

final readonly class AccountScopedWithdraw
{
    public function __construct(
        private AccountWithdraw $withdraw,
        private ?AccountWithdrawPix $withdrawPix,
    ) {
    }

    public function withdraw(): AccountWithdraw
    {
        return $this->withdraw;
    }

    public function withdrawPix(): ?AccountWithdrawPix
    {
        return $this->withdrawPix;
    }
}
