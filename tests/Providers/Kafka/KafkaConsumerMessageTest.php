<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final class KafkaConsumerMessageTest extends TestCase
{
    public function testNormalizesConsumedMessageFields(): void
    {
        $message = new KafkaConsumerMessage(
            topic: ' withdraw.process ',
            payload: ' {"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"} ',
            key: ' account-1 ',
            headers: [' correlation_id ' => ' corr-1 '],
            partition: 3,
            offset: 19,
        );

        self::assertSame('withdraw.process', $message->topic());
        self::assertSame(
            '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            $message->payload()
        );
        self::assertSame('account-1', $message->key());
        self::assertSame(['correlation_id' => 'corr-1'], $message->headers());
        self::assertSame(
            [
                'event_name' => 'withdraw.created',
                'correlation_id' => 'corr-1',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ],
            $message->payloadData()
        );
        self::assertSame(3, $message->partition());
        self::assertSame(19, $message->offset());
        self::assertSame('corr-1', $message->correlationId());
        self::assertSame('withdraw.created', $message->eventName());
        self::assertSame('2026-03-28T10:00:00-03:00', $message->occurredAt());
    }

    public function testReadsRequiredMetadataFromPayloadWhenHeaderIsMissing(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-2","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: null,
            headers: [],
            partition: 0,
            offset: 1,
        );

        self::assertSame('corr-2', $message->correlationId());
    }

    public function testRejectsMalformedJsonPayload(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Kafka message payload must contain valid JSON');

        new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":',
            key: null,
            headers: [],
            partition: 0,
            offset: 1,
        );
    }

    public function testRejectsPayloadWithoutRequiredMetadata(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('Kafka message must define required metadata "correlation_id".');

        new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: null,
            headers: [],
            partition: 0,
            offset: 1,
        );
    }
}
