<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface PublishDomainEventCommand extends Traceable
{
    public function eventName(): string;

    /**
     * @return array<string, mixed>
     */
    public function payload(): array;

}
