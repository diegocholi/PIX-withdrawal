<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawOutput;

interface ProcessWithdraw
{
    public function execute(ProcessWithdrawCommand $command): ProcessWithdrawOutput;
}
