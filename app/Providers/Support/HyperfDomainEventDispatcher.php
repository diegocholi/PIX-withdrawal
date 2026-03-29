<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Support;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;

final readonly class HyperfDomainEventDispatcher implements DomainEventDispatcher
{
    public function __construct(private EventDispatcherInterface $eventDispatcher)
    {
    }

    public function dispatch(DomainEvent $event): void
    {
        $this->eventDispatcher->dispatch($event);
    }
}
