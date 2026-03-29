<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final readonly class WithdrawNotificationKafkaMessageMapper
{
    public function map(KafkaConsumerMessage $message): WithdrawNotificationDispatch
    {
        if ($message->eventName() !== 'notification.email.withdraw') {
            throw InvalidWithdrawNotificationMessage::unsupportedEvent($message->eventName());
        }

        $payload = $this->requiredObject($message->payloadData(), 'payload');
        $event = $this->requiredObject($payload, 'event');

        return new WithdrawNotificationDispatch(
            event: new GenericDomainEvent(
                eventName: $this->requiredString($event, 'event_name'),
                aggregateId: $this->requiredString($event, 'aggregate_id'),
                occurredAt: $this->requiredString($event, 'occurred_at'),
                correlationId: $this->requiredString($event, 'correlation_id'),
                traceMetadata: $this->optionalObject($event, 'trace_metadata'),
                payload: $this->optionalObject($event, 'payload'),
            ),
            recipient: $this->requiredString($payload, 'recipient'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function requiredObject(array $payload, string $field): array
    {
        $value = $payload[$field] ?? null;

        if (is_array($value) && ! array_is_list($value)) {
            return $value;
        }

        throw is_array($value)
            ? InvalidWithdrawNotificationMessage::invalidObjectField($field)
            : InvalidWithdrawNotificationMessage::missingField($field);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function optionalObject(array $payload, string $field): array
    {
        $value = $payload[$field] ?? [];

        if (! is_array($value)) {
            throw InvalidWithdrawNotificationMessage::invalidObjectField($field);
        }

        if ($value === []) {
            return [];
        }

        if (array_is_list($value)) {
            throw InvalidWithdrawNotificationMessage::invalidObjectField($field);
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (is_string($value) && trim($value) !== '') {
            return trim($value);
        }

        throw InvalidWithdrawNotificationMessage::missingField($field);
    }
}
