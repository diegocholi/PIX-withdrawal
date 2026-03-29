<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Worker;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\InvalidWithdrawProcessingMessage;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawProcessingKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaPayloadContract;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final class WithdrawProcessingKafkaMessageMapperTest extends TestCase
{
    public function testMapsWithdrawQueuedMessageIntoProcessWithdrawInput(): void
    {
        $input = (new WithdrawProcessingKafkaMessageMapper())->map(new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.queued","aggregate_id":"wd-1","occurred_at":"2026-03-29T10:00:00-03:00","correlation_id":"corr-1","trace_metadata":{"origin":"scheduler"},"payload":{"withdraw_id":"wd-1","account_id":"acc-1","method":"PIX","status":"QUEUED","amount":"10.00","pix_key_type":"EMAIL","queued_at":"2026-03-29T10:00:00-03:00","scheduled_at":null}}',
            key: 'acc-1',
            headers: [],
            partition: 2,
            offset: 18,
        ));

        self::assertSame('wd-1', $input->withdrawId());
        self::assertSame('corr-1', $input->correlationId());
        self::assertSame(1, $input->attempt());
        self::assertSame('scheduler', $input->traceMetadata()['origin']);
        self::assertSame('cli.withdraw_worker', $input->traceMetadata()['source']);
        self::assertSame('withdraw.process', $input->traceMetadata()['kafka_topic']);
        self::assertSame(2, $input->traceMetadata()['kafka_partition']);
        self::assertSame(18, $input->traceMetadata()['kafka_offset']);
        self::assertSame('withdraw.queued', $input->traceMetadata()['kafka_event_name']);
    }

    public function testRejectsUnsupportedEventName(): void
    {
        $this->expectException(InvalidWithdrawProcessingMessage::class);
        $this->expectExceptionMessage(
            'Withdraw processing worker supports only "withdraw.queued" messages, got "withdraw.processed".',
        );

        (new WithdrawProcessingKafkaMessageMapper())->map(new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.processed","aggregate_id":"wd-1","occurred_at":"2026-03-29T10:00:00-03:00","correlation_id":"corr-1","payload":{"withdraw_id":"wd-1"}}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));
    }

    public function testRejectsMessageWithoutWithdrawId(): void
    {
        $this->expectException(InvalidKafkaPayloadContract::class);
        $this->expectExceptionMessage(
            'Kafka payload contract field "withdraw_id" is required for event "withdraw.queued".',
        );

        (new WithdrawProcessingKafkaMessageMapper())->map(new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.queued","aggregate_id":"wd-1","occurred_at":"2026-03-29T10:00:00-03:00","correlation_id":"corr-1","payload":{"account_id":"acc-1"}}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));
    }
}
