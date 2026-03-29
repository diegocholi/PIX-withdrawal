<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;

interface EventPayloadSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(DomainEvent $event): array;
}
