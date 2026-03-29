<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerHandler;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageConsumer;
use Tecnofit\PixWithdrawal\Providers\Kafka\RdkafkaConsumer;
use RdKafka\KafkaConsumer;

final class KafkaConsumerIntegrationTest extends KafkaIntegrationTestCase
{
    public function testConsumesRealKafkaMessageWithManualCommitAndSafeDeserialization(): void
    {
        $topic = $this->uniqueTopic('it.withdraw.process');
        $groupId = $this->uniqueGroupId('consumer-it');

        $this->publishRawMessage(
            $topic,
            json_encode([
                'event_name' => 'withdraw.created',
                'aggregate_id' => 'wd-it-2',
                'occurred_at' => '2026-03-28T18:05:00-03:00',
                'correlation_id' => 'corr-it-2',
                'trace_metadata' => ['source' => 'integration-test'],
                'payload' => [
                    'withdraw_id' => 'wd-it-2',
                    'account_id' => 'acc-it-2',
                    'method' => 'PIX',
                    'status' => 'PENDING',
                    'amount' => '17.30',
                    'pix_key_type' => 'EMAIL',
                    'scheduled_at' => null,
                ],
            ], JSON_THROW_ON_ERROR),
            'acc-it-2',
            [
                'correlation_id' => 'corr-it-2',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T18:05:00-03:00',
            ],
        );

        $receivedMessage = null;
        $handler = new class ($receivedMessage) implements KafkaConsumerHandler {
            public ?KafkaConsumerMessage $receivedMessage = null;

            public function __construct(?KafkaConsumerMessage $receivedMessage)
            {
                $this->receivedMessage = $receivedMessage;
            }

            public function handle(KafkaConsumerMessage $message): void
            {
                $this->receivedMessage = $message;
            }
        };

        $consumer = new KafkaMessageConsumer(
            new RdkafkaConsumer(new KafkaConsumer($this->consumerConfForTest($groupId))),
            $this->nullStructuredLogger(),
            $topic,
            250,
            null,
            new KafkaConsumeFailurePolicy($this->nullStructuredLogger()),
        );

        self::assertSame(1, $consumer->consume($handler, 1));
        self::assertInstanceOf(KafkaConsumerMessage::class, $handler->receivedMessage);
        self::assertSame('corr-it-2', $handler->receivedMessage->correlationId());
        self::assertSame('withdraw.created', $handler->receivedMessage->eventName());
        self::assertSame($topic, $handler->receivedMessage->topic());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'event_name' => 'withdraw.created',
                'aggregate_id' => 'wd-it-2',
                'occurred_at' => '2026-03-28T18:05:00-03:00',
                'correlation_id' => 'corr-it-2',
                'trace_metadata' => ['source' => 'integration-test'],
                'payload' => [
                    'withdraw_id' => 'wd-it-2',
                    'account_id' => 'acc-it-2',
                    'method' => 'PIX',
                    'status' => 'PENDING',
                    'amount' => '17.30',
                    'pix_key_type' => 'EMAIL',
                    'scheduled_at' => null,
                ],
            ], JSON_THROW_ON_ERROR),
            $handler->receivedMessage->payload(),
        );

        $messageAfterCommit = $this->rawKafkaMessage($topic, $groupId, 2000);

        self::assertNull(
            $messageAfterCommit,
            'Kafka consumer manual commit should prevent the same group from receiving the message again.',
        );
    }

    private function consumerConfForTest(string $groupId): \RdKafka\Conf
    {
        $conf = new \RdKafka\Conf();
        $conf->set('client.id', 'pix-withdrawal-consumer-integration');
        $conf->set('bootstrap.servers', $this->brokers());
        $conf->set('group.id', $groupId);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', 'earliest');

        return $conf;
    }
}
