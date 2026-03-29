<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface ProcessWithdrawCommand extends Traceable
{
    public function withdrawId(): string;

    public function attempt(): int;
}
