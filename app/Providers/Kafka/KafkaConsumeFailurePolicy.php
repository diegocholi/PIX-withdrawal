<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class KafkaConsumeFailurePolicy
{
    public function __construct(private StructuredLogger $structuredLogger)
    {
    }

    public function handlePollFailure(string $topic, Throwable $throwable): never
    {
        $this->logPollFailure($topic, $throwable);

        if ($throwable instanceof KafkaConsumeException && $throwable->isRetryable()) {
            throw new TransientInfrastructureException(
                'Kafka message consumption failed due to a transient broker error.',
                context: [
                    'provider' => 'kafka',
                    'topic' => $topic,
                ],
                previous: $throwable,
            );
        }

        throw $throwable;
    }

    public function handleMessageFailure(KafkaConsumerMessage $message, Throwable $throwable): never
    {
        $this->logMessageFailure($message, $throwable);

        if ($throwable instanceof KafkaConsumeException && $throwable->isRetryable()) {
            throw new TransientInfrastructureException(
                'Kafka message consumption failed due to a transient broker error.',
                context: [
                    'provider' => 'kafka',
                    'topic' => $message->topic(),
                    'event_name' => $message->eventName(),
                ],
                previous: $throwable,
            );
        }

        throw $throwable;
    }

    private function logPollFailure(string $topic, Throwable $throwable): void
    {
        $context = new LogContext(
            correlationId: 'kafka-consumer',
            errorCode: $this->errorCode($throwable),
            context: [
                'provider' => 'kafka',
                'operation' => 'kafka.consume',
                'topic' => $topic,
                'reason' => $throwable->getMessage(),
                'retryable' => $throwable instanceof KafkaConsumeException && $throwable->isRetryable(),
            ],
        );

        if ($throwable instanceof InvalidKafkaMessage) {
            $this->structuredLogger->error('kafka.consume.invalid_message', $context);

            return;
        }

        if ($throwable instanceof KafkaConsumeException && $throwable->isRetryable()) {
            $this->structuredLogger->warning('kafka.consume.failed.retryable', $context);

            return;
        }

        $this->structuredLogger->error('kafka.consume.failed', $context);
    }

    private function logMessageFailure(KafkaConsumerMessage $message, Throwable $throwable): void
    {
        $context = new LogContext(
            correlationId: $message->correlationId(),
            errorCode: $this->errorCode($throwable),
            context: [
                'provider' => 'kafka',
                'operation' => 'kafka.consume',
                'topic' => $message->topic(),
                'partition' => $message->partition(),
                'offset' => $message->offset(),
                'event_name' => $message->eventName(),
                'reason' => $throwable->getMessage(),
                'retryable' => $throwable instanceof KafkaConsumeException && $throwable->isRetryable(),
            ],
        );

        if ($throwable instanceof InvalidKafkaMessage || $throwable instanceof InvalidKafkaPayloadContract) {
            $this->structuredLogger->error('kafka.consume.invalid_payload', $context);

            return;
        }

        if ($throwable instanceof KafkaConsumeException && $throwable->isRetryable()) {
            $this->structuredLogger->warning('kafka.consume.failed.retryable', $context);

            return;
        }

        $this->structuredLogger->error('kafka.consume.failed', $context);
    }

    private function errorCode(Throwable $throwable): string
    {
        return match (true) {
            $throwable instanceof InvalidKafkaMessage => 'kafka.invalid_message',
            $throwable instanceof InvalidKafkaPayloadContract => 'kafka.invalid_payload',
            $throwable instanceof KafkaConsumeException && $throwable->isRetryable() => 'kafka.consume.transient_failure',
            default => 'kafka.consume.failed',
        };
    }
}
