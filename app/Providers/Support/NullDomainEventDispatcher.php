<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Support;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;

final class NullDomainEventDispatcher implements DomainEventDispatcher
{
    public function dispatch(DomainEvent $event): void
    {
    }
}
