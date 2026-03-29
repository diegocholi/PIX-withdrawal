<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerRecord;

final class KafkaProducerRecordTest extends TestCase
{
    public function testNormalizesTopicPayloadKeyAndHeaders(): void
    {
        $record = new KafkaProducerRecord(
            topic: ' withdraw.process ',
            payload: ' {"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"} ',
            key: ' account-1 ',
            headers: [
                ' correlation_id ' => ' corr-1 ',
                ' event_name ' => ' withdraw.created ',
                ' occurred_at ' => ' 2026-03-28T10:00:00-03:00 ',
            ],
        );

        self::assertSame('withdraw.process', $record->topic());
        self::assertSame('{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}', $record->payload());
        self::assertSame('account-1', $record->key());
        self::assertSame(
            [
                'correlation_id' => 'corr-1',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ],
            $record->headers()
        );
        self::assertSame('corr-1', $record->correlationId());
        self::assertSame('withdraw.created', $record->eventName());
        self::assertSame('2026-03-28T10:00:00-03:00', $record->occurredAt());
        self::assertSame(RD_KAFKA_PARTITION_UA, $record->partition());
    }

    public function testRejectsBlankTopic(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka topic must be non-empty.');

        new KafkaProducerRecord('   ', '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}');
    }

    public function testRejectsBlankPayload(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka payload must be non-empty.');

        new KafkaProducerRecord('withdraw.process', '   ');
    }

    public function testRejectsBlankHeaderName(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka header name must be non-empty.');

        new KafkaProducerRecord(
            'withdraw.process',
            '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            null,
            ['   ' => 'corr-1']
        );
    }

    public function testRejectsBlankHeaderValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka header "correlation_id" must be non-empty.');

        new KafkaProducerRecord(
            'withdraw.process',
            '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            null,
            ['correlation_id' => '   ']
        );
    }

    public function testHydratesRequiredOperationalHeadersFromPayload(): void
    {
        $record = new KafkaProducerRecord(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-9","occurred_at":"2026-03-28T10:00:00-03:00","trace_metadata":{"cli_correlation_id":"corr-cli-9"},"payload":{"account_id":"account-9"}}',
        );

        self::assertSame(
            [
                'correlation_id' => 'corr-9',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ],
            $record->headers()
        );
        self::assertSame('account-9', $record->key());
        self::assertSame(['cli_correlation_id' => 'corr-cli-9'], $record->traceMetadata());
    }

    public function testKeepsExplicitPartitionKeyWhenProvided(): void
    {
        $record = new KafkaProducerRecord(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-9","occurred_at":"2026-03-28T10:00:00-03:00","payload":{"account_id":"account-9"}}',
            key: 'routing-account-42',
        );

        self::assertSame('routing-account-42', $record->key());
    }

    public function testRejectsMissingRequiredOperationalHeaders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka header "correlation_id" is required for published messages.');

        new KafkaProducerRecord(
            'withdraw.process',
            '{"payload":"without-operational-headers"}',
        );
    }
}
