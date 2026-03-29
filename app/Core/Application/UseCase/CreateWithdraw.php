<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawOutput;

interface CreateWithdraw
{
    public function execute(CreateWithdrawCommand $command): CreateWithdrawOutput;
}
