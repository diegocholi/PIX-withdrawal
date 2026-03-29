<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class KafkaPublishFailurePolicy
{
    public function __construct(private StructuredLogger $structuredLogger)
    {
    }

    public function handle(KafkaProducerRecord $record, Throwable $throwable): never
    {
        $this->logFailure($record, $throwable);

        if ($throwable instanceof KafkaPublishException && $throwable->isRetryable()) {
            throw new TransientInfrastructureException(
                'Kafka message publication failed due to a transient broker error.',
                context: [
                    'provider' => 'kafka',
                    'topic' => $record->topic(),
                    'event_name' => $record->eventName(),
                ],
                previous: $throwable,
            );
        }

        throw $throwable;
    }

    private function logFailure(KafkaProducerRecord $record, Throwable $throwable): void
    {
        $context = new LogContext(
            correlationId: $record->correlationId(),
            errorCode: $this->errorCode($throwable),
            traceMetadata: $record->traceMetadata(),
            context: [
                'provider' => 'kafka',
                'operation' => 'kafka.publish',
                'topic' => $record->topic(),
                'partition' => $record->partition(),
                'event_name' => $record->eventName(),
                'reason' => $throwable->getMessage(),
                'retryable' => $throwable instanceof KafkaPublishException && $throwable->isRetryable(),
            ],
        );

        if ($throwable instanceof KafkaPublishException && $throwable->isRetryable()) {
            $this->structuredLogger->warning('kafka.publish.failed.retryable', $context);

            return;
        }

        $this->structuredLogger->error('kafka.publish.failed', $context);
    }

    private function errorCode(Throwable $throwable): string
    {
        if ($throwable instanceof KafkaPublishException && $throwable->isRetryable()) {
            return 'kafka.publish.transient_failure';
        }

        return 'kafka.publish.failed';
    }
}
