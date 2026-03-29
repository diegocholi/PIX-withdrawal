<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Query;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface FindWithdrawStatusQuery extends Traceable
{
    public function accountId(): string;

    public function withdrawId(): string;
}
