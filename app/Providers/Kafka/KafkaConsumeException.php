<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final class KafkaConsumeException extends \RuntimeException
{
    public function __construct(
        string $message,
        private readonly bool $retryable = false,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    /**
     * @param list<string> $topics
     */
    public static function pollingFailed(array $topics, int $code, string $reason): self
    {
        return new self(
            sprintf(
                'Kafka consumer polling failed for topics "%s" with code %d: %s.',
                implode(', ', $topics),
                $code,
                $reason,
            ),
            true,
        );
    }

    public static function commitFailed(KafkaConsumerMessage $message, \Throwable $previous): self
    {
        return new self(
            sprintf(
                'Kafka consumer commit failed for topic "%s", partition %d, offset %d.',
                $message->topic(),
                $message->partition(),
                $message->offset(),
            ),
            true,
            $previous,
        );
    }

    public function isRetryable(): bool
    {
        return $this->retryable;
    }
}
