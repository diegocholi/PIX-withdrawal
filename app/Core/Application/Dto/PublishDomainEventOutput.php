<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Dto;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface PublishDomainEventOutput extends Traceable
{
    public function eventName(): string;
}
