<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final class KafkaPublishException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function flushFailed(string $topic, int $code): self
    {
        return new self(
            sprintf('Kafka producer flush failed for topic "%s" with code %d.', $topic, $code),
            true,
        );
    }

    public static function transportFailure(string $topic, \Throwable $previous): self
    {
        return new self(
            sprintf('Kafka message publication failed for topic "%s".', $topic),
            true,
            $previous,
        );
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
