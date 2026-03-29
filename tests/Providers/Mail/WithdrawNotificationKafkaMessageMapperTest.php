<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Mail;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\InvalidWithdrawNotificationMessage;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaMessageMapper;

final class WithdrawNotificationKafkaMessageMapperTest extends TestCase
{
    public function testMapsNotificationKafkaMessageToDispatchContract(): void
    {
        $mapper = new WithdrawNotificationKafkaMessageMapper();

        $dispatch = $mapper->map(new KafkaConsumerMessage(
            topic: 'notification.email.withdraw',
            payload: json_encode([
                'event_name' => 'notification.email.withdraw',
                'correlation_id' => 'corr-notify',
                'occurred_at' => '2026-03-28T10:04:00-03:00',
                'payload' => [
                    'recipient' => 'customer@example.test',
                    'event' => [
                        'event_name' => 'withdraw.processed',
                        'aggregate_id' => 'wd-1',
                        'occurred_at' => '2026-03-28T10:03:00-03:00',
                        'correlation_id' => 'corr-1',
                        'trace_metadata' => ['trace_id' => 'trace-1'],
                        'payload' => [
                            'amount' => '25.00',
                            'pix_key_type' => 'EMAIL',
                            'pix_key_masked' => 'u***@example.com',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            key: 'wd-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));

        self::assertSame('customer@example.test', $dispatch->recipient());
        self::assertSame('withdraw.processed', $dispatch->event()->eventName());
        self::assertSame('wd-1', $dispatch->event()->aggregateId());
        self::assertSame('corr-1', $dispatch->event()->correlationId());
        self::assertSame(['trace_id' => 'trace-1'], $dispatch->event()->traceMetadata());
        self::assertSame('u***@example.com', $dispatch->event()->payload()['pix_key_masked']);
    }

    public function testRejectsUnsupportedKafkaNotificationEvent(): void
    {
        $mapper = new WithdrawNotificationKafkaMessageMapper();

        $this->expectException(InvalidWithdrawNotificationMessage::class);
        $this->expectExceptionMessage(
            'Withdraw notification Kafka handler supports only "notification.email.withdraw" messages, got "withdraw.processed".'
        );

        $mapper->map(new KafkaConsumerMessage(
            topic: 'withdraw.succeeded',
            payload: '{"event_name":"withdraw.processed","correlation_id":"corr-1","occurred_at":"2026-03-28T10:04:00-03:00","payload":{}}',
            key: 'wd-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));
    }

    public function testFailsWhenNotificationMessageDoesNotExposeRecipient(): void
    {
        $mapper = new WithdrawNotificationKafkaMessageMapper();

        $this->expectException(InvalidWithdrawNotificationMessage::class);
        $this->expectExceptionMessage(
            'Withdraw notification Kafka message requires the field "recipient".'
        );

        $mapper->map(new KafkaConsumerMessage(
            topic: 'notification.email.withdraw',
            payload: '{"event_name":"notification.email.withdraw","correlation_id":"corr-notify","occurred_at":"2026-03-28T10:04:00-03:00","payload":{"event":{"event_name":"withdraw.processed","aggregate_id":"wd-1","occurred_at":"2026-03-28T10:03:00-03:00","correlation_id":"corr-1","payload":{"amount":"25.00","pix_key_type":"EMAIL","pix_key_masked":"u***@example.com"}}}}',
            key: 'wd-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));
    }
}
