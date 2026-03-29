<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

interface ProcessableWithdrawQuery
{
    public function lockById(string $withdrawId): ?ProcessableWithdraw;
}
