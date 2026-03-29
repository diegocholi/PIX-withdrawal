<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerRecord;

final readonly class WithdrawNotificationKafkaRecordFactory
{
    public function __construct(
        private EventPayloadSerializer $eventPayloadSerializer,
        private KafkaConfig $kafkaConfig,
    ) {
    }

    public function create(DomainEvent $event, string $recipient): KafkaProducerRecord
    {
        $normalizedRecipient = trim($recipient);

        if ($normalizedRecipient === '') {
            throw new \InvalidArgumentException('Withdraw notification recipient must be non-empty.');
        }

        $payload = [
            'event_name' => 'notification.email.withdraw',
            'aggregate_id' => $event->aggregateId(),
            'occurred_at' => $event->occurredAt(),
            'correlation_id' => $event->correlationId(),
            'trace_metadata' => $event->traceMetadata(),
            'payload' => [
                'recipient' => $normalizedRecipient,
                'event' => $this->eventPayloadSerializer->serialize($event),
            ],
        ];

        try {
            $serializedPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                sprintf(
                    'Kafka payload serialization failed for notification event "%s".',
                    $event->eventName(),
                ),
                previous: $exception,
            );
        }

        return new KafkaProducerRecord(
            topic: $this->kafkaConfig->notificationEmailWithdrawTopic(),
            payload: $serializedPayload,
            key: $event->aggregateId(),
        );
    }
}
