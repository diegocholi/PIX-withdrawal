<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;

interface DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void;
}
