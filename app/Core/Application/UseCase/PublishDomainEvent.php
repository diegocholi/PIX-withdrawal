<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\UseCase;

use Tecnofit\PixWithdrawal\Core\Application\Command\PublishDomainEventCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\PublishDomainEventOutput;

interface PublishDomainEvent
{
    public function execute(PublishDomainEventCommand $command): PublishDomainEventOutput;
}
