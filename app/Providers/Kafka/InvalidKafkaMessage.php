<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final class InvalidKafkaMessage extends \UnexpectedValueException
{
    public static function invalidJsonPayload(\JsonException $exception): self
    {
        return new self(
            sprintf('Kafka message payload must contain valid JSON: %s', $exception->getMessage()),
            previous: $exception,
        );
    }

    public static function invalidPayloadShape(): self
    {
        return new self('Kafka message payload must decode to a JSON object.');
    }

    public static function missingRequiredMetadata(string $field): self
    {
        return new self(sprintf('Kafka message must define required metadata "%s".', $field));
    }
}
