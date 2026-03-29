<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final class InvalidKafkaPayloadContract extends \InvalidArgumentException
{
    public static function missingField(string $field, string $eventName): self
    {
        return new self(sprintf(
            'Kafka payload contract field "%s" is required for event "%s".',
            $field,
            $eventName,
        ));
    }

    public static function invalidField(string $field, string $eventName, string $expectedType): self
    {
        return new self(sprintf(
            'Kafka payload contract field "%s" for event "%s" must be %s.',
            $field,
            $eventName,
            $expectedType,
        ));
    }
}
