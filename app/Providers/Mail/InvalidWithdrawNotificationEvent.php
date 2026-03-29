<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final class InvalidWithdrawNotificationEvent extends \InvalidArgumentException
{
    public static function unsupportedEvent(string $eventName): self
    {
        return new self(sprintf(
            'Withdraw notification renderer supports only "withdraw.processed" events, got "%s".',
            trim($eventName),
        ));
    }

    public static function missingField(string $field): self
    {
        return new self(sprintf(
            'Withdraw notification renderer requires the field "%s" in the serialized event payload.',
            trim($field),
        ));
    }
}
