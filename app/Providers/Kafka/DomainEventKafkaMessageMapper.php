<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Domain\Event\DomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaRecordFactory;

final readonly class DomainEventKafkaMessageMapper
{
    public function __construct(
        private EventPayloadSerializer $eventPayloadSerializer,
        private KafkaConfig $kafkaConfig,
        private WithdrawNotificationRecipientQuery $withdrawNotificationRecipientQuery,
        private DomainEventKafkaPayloadFactory $payloadFactory,
        private WithdrawNotificationKafkaRecordFactory $withdrawNotificationKafkaRecordFactory,
    ) {
    }

    public function map(DomainEvent $event): KafkaProducerRecord
    {
        $topic = $this->topicFor($event);
        $payload = $this->payloadFactory->create($this->eventPayloadSerializer->serialize($event));

        try {
            $serializedPayload = json_encode($payload->toArray(), JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new \RuntimeException(
                sprintf('Kafka payload serialization failed for event "%s".', $event->eventName()),
                previous: $exception,
            );
        }

        return new KafkaProducerRecord(
            topic: $topic,
            payload: $serializedPayload,
        );
    }

    /**
     * @return list<KafkaProducerRecord>
     */
    public function mapAll(DomainEvent $event): array
    {
        $records = [$this->map($event)];
        $notificationRecord = $this->notificationRecordFor($event);

        if ($notificationRecord !== null) {
            $records[] = $notificationRecord;
        }

        return $records;
    }

    private function topicFor(DomainEvent $event): string
    {
        return match ($event->eventName()) {
            'withdraw.queued' => $this->kafkaConfig->withdrawProcessTopic(),
            'withdraw.processed' => $this->kafkaConfig->withdrawSucceededTopic(),
            'withdraw.failed' => $this->kafkaConfig->withdrawFailedTopic(),
            default => throw new \InvalidArgumentException(sprintf(
                'Kafka topic mapping is not defined for event "%s".',
                $event->eventName(),
            )),
        };
    }

    private function notificationRecordFor(DomainEvent $event): ?KafkaProducerRecord
    {
        if ($event->eventName() !== 'withdraw.processed') {
            return null;
        }

        $recipient = $this->withdrawNotificationRecipientQuery->findRecipientByWithdrawId($event->aggregateId());

        if ($recipient === null) {
            return null;
        }

        return $this->withdrawNotificationKafkaRecordFactory->create($event, $recipient);
    }
}
