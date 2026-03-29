<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Event;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;

interface DomainEvent extends SerializableDto, Traceable
{
    public function eventName(): string;

    public function aggregateId(): string;

    public function occurredAt(): string;
}
