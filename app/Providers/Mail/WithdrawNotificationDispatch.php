<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;

final readonly class WithdrawNotificationDispatch
{
    public function __construct(
        private DomainEvent $event,
        private string $recipient,
    ) {
    }

    public function event(): DomainEvent
    {
        return $this->event;
    }

    public function recipient(): string
    {
        return trim($this->recipient);
    }
}
