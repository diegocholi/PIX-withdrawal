<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawInput;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaPayloadContract;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final class WithdrawProcessingKafkaMessageMapper
{
    public function map(KafkaConsumerMessage $message): ProcessWithdrawInput
    {
        $eventName = $message->eventName();

        if ($eventName !== 'withdraw.queued') {
            throw InvalidWithdrawProcessingMessage::unsupportedEvent($eventName);
        }

        $payload = $this->requiredArray($message->payloadData(), 'payload', $eventName);
        $traceMetadata = $this->traceMetadata($message->payloadData(), $eventName);

        return new ProcessWithdrawInput(
            withdrawId: $this->requiredString($payload, 'withdraw_id', $eventName),
            correlationId: $message->correlationId(),
            attempt: 1,
            traceMetadata: array_merge($traceMetadata, [
                'source' => 'cli.withdraw_worker',
                'kafka_topic' => $message->topic(),
                'kafka_partition' => $message->partition(),
                'kafka_offset' => $message->offset(),
                'kafka_event_name' => $eventName,
            ]),
        );
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

        $value = $payload['trace_metadata'];

        if (! is_array($value)) {
            throw InvalidKafkaPayloadContract::invalidField('trace_metadata', $eventName, 'an array');
        }

        /** @var array<string, mixed> $value */
        return $value;
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
}
