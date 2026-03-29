<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusOutput;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusQuery;

interface FindWithdrawStatus
{
    public function execute(FindWithdrawStatusQuery $query): FindWithdrawStatusOutput;
}
