<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class KafkaMessageProducer
{
    public function __construct(
        private KafkaProducerTransport $transport,
        private StructuredLogger $structuredLogger,
        private KafkaPublishFailurePolicy $failurePolicy,
    ) {
    }

    public function publish(KafkaProducerRecord $record): void
    {
        $this->structuredLogger->info(
            'kafka.publish.attempt',
            new LogContext(
                correlationId: $record->correlationId(),
                traceMetadata: $record->traceMetadata(),
                context: [
                    'provider' => 'kafka',
                    'operation' => 'kafka.publish',
                    'topic' => $record->topic(),
                    'partition' => $record->partition(),
                    'event_name' => $record->eventName(),
                ],
            )
        );

        try {
            $this->transport->publish($record);
        } catch (\Throwable $throwable) {
            $this->failurePolicy->handle($record, $throwable);
        }
    }
}
