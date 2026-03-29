<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Providers\Kafka\Payload\KafkaEventPayload;
use Tecnofit\PixWithdrawal\Providers\Kafka\Payload\WithdrawFailedPayload;
use Tecnofit\PixWithdrawal\Providers\Kafka\Payload\WithdrawProcessedPayload;
use Tecnofit\PixWithdrawal\Providers\Kafka\Payload\WithdrawQueuedPayload;

final class DomainEventKafkaPayloadFactory
{
    /**
     * @param array<string, mixed> $serializedEvent
     */
    public function create(array $serializedEvent): KafkaEventPayload
    {
        $eventName = $this->requiredString($serializedEvent, 'event_name', 'unknown');

        return new KafkaEventPayload(
            eventName: $eventName,
            aggregateId: $this->requiredString($serializedEvent, 'aggregate_id', $eventName),
            occurredAt: $this->requiredString($serializedEvent, 'occurred_at', $eventName),
            correlationId: $this->requiredString($serializedEvent, 'correlation_id', $eventName),
            traceMetadata: $this->traceMetadata($serializedEvent, $eventName),
            payload: $this->payloadFor($eventName, $this->requiredArray($serializedEvent, 'payload', $eventName)),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function payloadFor(string $eventName, array $payload): SerializableDto
    {
        return match ($eventName) {
            'withdraw.queued' => new WithdrawQueuedPayload(
                withdrawId: $this->requiredString($payload, 'withdraw_id', $eventName),
                accountId: $this->requiredString($payload, 'account_id', $eventName),
                method: $this->requiredString($payload, 'method', $eventName),
                status: $this->requiredString($payload, 'status', $eventName),
                amount: $this->requiredString($payload, 'amount', $eventName),
                pixKeyType: $this->nullableString($payload, 'pix_key_type', $eventName),
                queuedAt: $this->nullableString($payload, 'queued_at', $eventName),
                scheduledAt: $this->nullableString($payload, 'scheduled_at', $eventName),
            ),
            'withdraw.processed' => new WithdrawProcessedPayload(
                withdrawId: $this->requiredString($payload, 'withdraw_id', $eventName),
                accountId: $this->requiredString($payload, 'account_id', $eventName),
                amount: $this->requiredString($payload, 'amount', $eventName),
                method: $this->requiredString($payload, 'method', $eventName),
                status: $this->requiredString($payload, 'status', $eventName),
                pixKeyType: $this->nullableString($payload, 'pix_key_type', $eventName),
                pixKeyMasked: $this->nullableString($payload, 'pix_key_masked', $eventName),
                errorReason: $this->nullableString($payload, 'error_reason', $eventName),
            ),
            'withdraw.failed' => new WithdrawFailedPayload(
                withdrawId: $this->requiredString($payload, 'withdraw_id', $eventName),
                accountId: $this->requiredString($payload, 'account_id', $eventName),
                amount: $this->requiredString($payload, 'amount', $eventName),
                method: $this->requiredString($payload, 'method', $eventName),
                status: $this->requiredString($payload, 'status', $eventName),
                pixKeyType: $this->nullableString($payload, 'pix_key_type', $eventName),
                pixKeyMasked: $this->nullableString($payload, 'pix_key_masked', $eventName),
                errorReason: $this->nullableString($payload, 'error_reason', $eventName),
            ),
            default => throw new \InvalidArgumentException(sprintf(
                'Kafka payload contract is not defined for event "%s".',
                $eventName,
            )),
        };
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requiredString(array $payload, string $field, string $eventName): string
    {
        if (! array_key_exists($field, $payload)) {
            throw InvalidKafkaPayloadContract::missingField($field, $eventName);
        }

        $value = $payload[$field];

        if (! is_scalar($value) || trim((string) $value) === '') {
            throw InvalidKafkaPayloadContract::invalidField($field, $eventName, 'a non-empty scalar');
        }

        return trim((string) $value);
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function requiredArray(array $payload, string $field, string $eventName): array
    {
        if (! array_key_exists($field, $payload)) {
            throw InvalidKafkaPayloadContract::missingField($field, $eventName);
        }

        $value = $payload[$field];

        if (! is_array($value)) {
            throw InvalidKafkaPayloadContract::invalidField($field, $eventName, 'an object-like array');
        }

        /** @var array<string, mixed> $value */
        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function traceMetadata(array $payload, string $eventName): array
    {
        if (! array_key_exists('trace_metadata', $payload) || $payload['trace_metadata'] === null) {
            return [];
        }

        if (! is_array($payload['trace_metadata'])) {
            throw InvalidKafkaPayloadContract::invalidField('trace_metadata', $eventName, 'an array');
        }

        /** @var array<string, mixed> $traceMetadata */
        $traceMetadata = $payload['trace_metadata'];

        return $traceMetadata;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function nullableString(array $payload, string $field, string $eventName): ?string
    {
        if (! array_key_exists($field, $payload) || $payload[$field] === null) {
            return null;
        }

        $value = $payload[$field];

        if (! is_scalar($value)) {
            throw InvalidKafkaPayloadContract::invalidField($field, $eventName, 'a scalar or null');
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }
}
