<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

final class InvalidWithdrawNotificationMessage extends \InvalidArgumentException
{
    public static function unsupportedEvent(string $eventName): self
    {
        return new self(sprintf(
            'Withdraw notification Kafka handler supports only "notification.email.withdraw" messages, got "%s".',
            trim($eventName),
        ));
    }

    public static function missingField(string $field): self
    {
        return new self(sprintf(
            'Withdraw notification Kafka message requires the field "%s".',
            trim($field),
        ));
    }

    public static function invalidObjectField(string $field): self
    {
        return new self(sprintf(
            'Withdraw notification Kafka message field "%s" must be an object-like associative array.',
            trim($field),
        ));
    }
}
