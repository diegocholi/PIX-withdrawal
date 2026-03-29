<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;

final readonly class ConfiguredWithdrawDuplicateGuardWindow implements WithdrawDuplicateGuardWindow
{
    public function __construct(private int $seconds)
    {
    }

    public function seconds(): int
    {
        return $this->seconds;
    }
}
