<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaPayloadFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaRecordFactory;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class DomainEventKafkaMessageMapperTest extends TestCase
{
    public function testMapsWithdrawQueuedEventToProcessingTopic(): void
    {
        $mapper = new DomainEventKafkaMessageMapper(
            new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            $this->kafkaConfig(),
            $this->recipientQuery(),
            new DomainEventKafkaPayloadFactory(),
            $this->notificationRecordFactory(),
        );

        $record = $mapper->map(new GenericDomainEvent(
            eventName: 'withdraw.queued',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T10:00:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'withdraw_id' => 'wd-1',
                'account_id' => 'acc-1',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '10.50',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T10:00:00-03:00',
                'scheduled_at' => null,
            ],
        ));

        self::assertSame('withdraw.process', $record->topic());
        self::assertSame('acc-1', $record->key());
        self::assertSame('withdraw.queued', $record->eventName());
        self::assertSame('corr-1', $record->correlationId());
        self::assertSame('2026-03-28T10:00:00-03:00', $record->occurredAt());
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'event_name' => 'withdraw.queued',
                'aggregate_id' => 'wd-1',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
                'correlation_id' => 'corr-1',
                'trace_metadata' => [],
                'payload' => [
                    'withdraw_id' => 'wd-1',
                    'account_id' => 'acc-1',
                    'method' => 'PIX',
                    'status' => 'QUEUED',
                    'amount' => '10.50',
                    'pix_key_type' => 'EMAIL',
                    'queued_at' => '2026-03-28T10:00:00-03:00',
                    'scheduled_at' => null,
                ],
            ], JSON_THROW_ON_ERROR),
            $record->payload(),
        );
    }

    public function testMapsFinalEventsToTheirCanonicalTopics(): void
    {
        $mapper = new DomainEventKafkaMessageMapper(
            new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            $this->kafkaConfig(),
            $this->recipientQuery(['wd-2' => 'customer@example.test']),
            new DomainEventKafkaPayloadFactory(),
            $this->notificationRecordFactory(),
        );

        $processed = new GenericDomainEvent(
            eventName: 'withdraw.processed',
            aggregateId: 'wd-2',
            occurredAt: '2026-03-28T10:01:00-03:00',
            correlationId: 'corr-2',
            payload: [
                'withdraw_id' => 'wd-2',
                'account_id' => 'acc-2',
                'amount' => '22.00',
                'method' => 'PIX',
                'status' => 'DONE',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'u***@example.com',
                'error_reason' => null,
            ],
        );

        $failed = $mapper->map(new GenericDomainEvent(
            eventName: 'withdraw.failed',
            aggregateId: 'wd-3',
            occurredAt: '2026-03-28T10:02:00-03:00',
            correlationId: 'corr-3',
            payload: [
                'withdraw_id' => 'wd-3',
                'account_id' => 'acc-3',
                'amount' => '31.00',
                'method' => 'PIX',
                'status' => 'FAILED',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'f***@example.com',
                'error_reason' => 'insufficient_funds',
            ],
        ));

        $processedRecords = $mapper->mapAll($processed);

        self::assertCount(2, $processedRecords);
        self::assertSame('withdraw.succeeded', $processedRecords[0]->topic());
        self::assertSame('notification.email.withdraw', $processedRecords[1]->topic());
        self::assertSame('wd-2', $processedRecords[1]->key());
        self::assertStringContainsString('"recipient":"customer@example.test"', $processedRecords[1]->payload());
        self::assertStringContainsString('"event_name":"withdraw.processed"', $processedRecords[1]->payload());
        self::assertSame('withdraw.failed', $failed->topic());
    }

    public function testRejectsSerializedEventWithoutRequiredKafkaPayloadContractField(): void
    {
        $mapper = new DomainEventKafkaMessageMapper(
            new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            $this->kafkaConfig(),
            $this->recipientQuery(),
            new DomainEventKafkaPayloadFactory(),
            $this->notificationRecordFactory(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Kafka payload contract field "account_id" is required for event "withdraw.queued".',
        );

        $mapper->map(new GenericDomainEvent(
            eventName: 'withdraw.queued',
            aggregateId: 'wd-5',
            occurredAt: '2026-03-28T10:04:00-03:00',
            correlationId: 'corr-5',
            payload: [
                'withdraw_id' => 'wd-5',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '10.00',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T10:04:00-03:00',
            ],
        ));
    }

    public function testRejectsUnsupportedEventMappings(): void
    {
        $mapper = new DomainEventKafkaMessageMapper(
            new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            $this->kafkaConfig(),
            $this->recipientQuery(),
            new DomainEventKafkaPayloadFactory(),
            $this->notificationRecordFactory(),
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Kafka topic mapping is not defined for event "notification.email.withdraw".');

        $mapper->map(new GenericDomainEvent(
            eventName: 'notification.email.withdraw',
            aggregateId: 'wd-4',
            occurredAt: '2026-03-28T10:03:00-03:00',
            correlationId: 'corr-4',
            payload: ['account_id' => 'acc-4'],
        ));
    }

    private function kafkaConfig(): KafkaConfig
    {
        return KafkaConfig::fromArray([
            'topics' => [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
                KafkaConfig::TOPIC_WITHDRAW_SUCCEEDED => 'withdraw.succeeded',
                KafkaConfig::TOPIC_WITHDRAW_FAILED => 'withdraw.failed',
                KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW => 'notification.email.withdraw',
            ],
        ]);
    }

    private function notificationRecordFactory(): WithdrawNotificationKafkaRecordFactory
    {
        return new WithdrawNotificationKafkaRecordFactory(
            new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
            $this->kafkaConfig(),
        );
    }

    /**
     * @param array<string, string> $recipientsByWithdrawId
     */
    private function recipientQuery(array $recipientsByWithdrawId = []): WithdrawNotificationRecipientQuery
    {
        return new class ($recipientsByWithdrawId) implements WithdrawNotificationRecipientQuery {
            /**
             * @param array<string, string> $recipientsByWithdrawId
             */
            public function __construct(private array $recipientsByWithdrawId)
            {
            }

            public function findRecipientByWithdrawId(string $withdrawId): ?string
            {
                return $this->recipientsByWithdrawId[$withdrawId] ?? null;
            }
        };
    }
}
