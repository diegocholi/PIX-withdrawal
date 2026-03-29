<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

interface WithdrawDuplicateGuardWindow
{
    public function seconds(): int;
}
