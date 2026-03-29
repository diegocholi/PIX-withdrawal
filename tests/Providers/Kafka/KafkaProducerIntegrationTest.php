<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaPayloadFactory;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaRecordFactory;
use Tecnofit\PixWithdrawal\Providers\Kafka\RdkafkaProducer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;
use RdKafka\Producer;

final class KafkaProducerIntegrationTest extends KafkaIntegrationTestCase
{
    public function testPublishesSerializedDomainEventToRealKafkaTopic(): void
    {
        $topic = $this->uniqueTopic('it.withdraw.process');
        $kafkaConfig = KafkaConfig::fromArray([
            'brokers' => [$this->brokers()],
            'topics' => [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS => $topic,
                KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED => $this->uniqueTopic('it.withdraw.succeeded'),
                KafkaConfig::TOPIC_WITHDRAW_FAILED => $this->uniqueTopic('it.withdraw.failed'),
                KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW => $this->uniqueTopic('it.notification.email.withdraw'),
            ],
        ]);
        $serializer = new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer());
        $dispatcher = new KafkaDomainEventDispatcher(
            fn () => new KafkaMessageProducer(
                new RdkafkaProducer(new Producer($this->producerConfForTest()), 5000),
                $this->nullStructuredLogger(),
                new KafkaPublishFailurePolicy($this->nullStructuredLogger()),
            ),
            new DomainEventKafkaMessageMapper(
                $serializer,
                $kafkaConfig,
                new class implements WithdrawNotificationRecipientQuery {
                    public function findRecipientByWithdrawId(string $withdrawId): ?string
                    {
                        return null;
                    }
                },
                new DomainEventKafkaPayloadFactory(),
                new WithdrawNotificationKafkaRecordFactory($serializer, $kafkaConfig),
            ),
        );

        $dispatcher->dispatch(new GenericDomainEvent(
            eventName: 'withdraw.queued',
            aggregateId: 'wd-it-1',
            occurredAt: '2026-03-28T18:00:00-03:00',
            correlationId: 'corr-it-1',
            payload: [
                'withdraw_id' => 'wd-it-1',
                'account_id' => 'acc-it-1',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '10.50',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T18:00:00-03:00',
                'scheduled_at' => null,
            ],
        ));

        $message = $this->rawKafkaMessage($topic, $this->uniqueGroupId('producer-it'));

        self::assertNotNull($message, 'Kafka producer integration test did not receive any message from the broker.');
        self::assertSame($topic, $message->topic_name);
        self::assertSame('acc-it-1', $message->key);
        self::assertSame([
            'correlation_id' => 'corr-it-1',
            'event_name' => 'withdraw.queued',
            'occurred_at' => '2026-03-28T18:00:00-03:00',
        ], $message->headers ?? []);
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'event_name' => 'withdraw.queued',
                'aggregate_id' => 'wd-it-1',
                'occurred_at' => '2026-03-28T18:00:00-03:00',
                'correlation_id' => 'corr-it-1',
                'trace_metadata' => [],
                'payload' => [
                    'withdraw_id' => 'wd-it-1',
                    'account_id' => 'acc-it-1',
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '10.50',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T18:00:00-03:00',
                    'scheduled_at' => null,
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $message->payload,
        );
    }

    private function producerConfForTest(): \RdKafka\Conf
    {
        $conf = new \RdKafka\Conf();
        $conf->set('client.id', 'pix-withdrawal-producer-integration');
        $conf->set('bootstrap.servers', $this->brokers());

        return $conf;
    }
}
