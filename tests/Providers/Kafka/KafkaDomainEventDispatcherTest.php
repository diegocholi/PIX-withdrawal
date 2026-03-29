<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Event\GenericDomainEvent;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawNotificationRecipientQuery;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Providers\Kafka\DomainEventKafkaPayloadFactory;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerRecord;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerTransport;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaRecordFactory;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final class KafkaDomainEventDispatcherTest extends TestCase
{
    public function testDispatchPublishesMappedEventThroughKafkaProducer(): void
    {
        $transport = new SpyKafkaProducerTransport();
        $dispatcher = new KafkaDomainEventDispatcher(
            static fn () => new KafkaMessageProducer(
                $transport,
                new NullStructuredLogger(),
                new KafkaPublishFailurePolicy(new NullStructuredLogger()),
            ),
            new DomainEventKafkaMessageMapper(
                new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                $this->kafkaConfig(),
                $this->recipientQuery(),
                new DomainEventKafkaPayloadFactory(),
                $this->notificationRecordFactory(),
            ),
        );

        $dispatcher->dispatch(new GenericDomainEvent(
            eventName: 'withdraw.queued',
            aggregateId: 'wd-1',
            occurredAt: '2026-03-28T11:00:00-03:00',
            correlationId: 'corr-1',
            payload: [
                'withdraw_id' => 'wd-1',
                'account_id' => 'acc-1',
                'method' => 'PIX',
                'status' => 'QUEUED',
                'amount' => '15.00',
                'pix_key_type' => 'EMAIL',
                'queued_at' => '2026-03-28T11:00:00-03:00',
            ],
        ));

        self::assertCount(1, $transport->records);
        self::assertSame('withdraw.process', $transport->records[0]->topic());
        self::assertSame('acc-1', $transport->records[0]->key());
        self::assertSame('withdraw.queued', $transport->records[0]->eventName());
    }

    public function testDispatchPublishesNotificationEnvelopeForProcessedWithdrawWithRecipient(): void
    {
        $transport = new SpyKafkaProducerTransport();
        $dispatcher = new KafkaDomainEventDispatcher(
            static fn () => new KafkaMessageProducer(
                $transport,
                new NullStructuredLogger(),
                new KafkaPublishFailurePolicy(new NullStructuredLogger()),
            ),
            new DomainEventKafkaMessageMapper(
                new SafeEventPayloadSerializer(new ProviderSensitiveDataMasker(), new ProviderPayloadNormalizer()),
                $this->kafkaConfig(),
                $this->recipientQuery(['wd-2' => 'notify@example.test']),
                new DomainEventKafkaPayloadFactory(),
                $this->notificationRecordFactory(),
            ),
        );

        $dispatcher->dispatch(new GenericDomainEvent(
            eventName: 'withdraw.processed',
            aggregateId: 'wd-2',
            occurredAt: '2026-03-28T11:05:00-03:00',
            correlationId: 'corr-2',
            payload: [
                'withdraw_id' => 'wd-2',
                'account_id' => 'acc-2',
                'amount' => '15.00',
                'method' => 'PIX',
                'status' => 'DONE',
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'n***@example.test',
                'error_reason' => null,
            ],
        ));

        self::assertCount(2, $transport->records);
        self::assertSame('withdraw.succeeded', $transport->records[0]->topic());
        self::assertSame('notification.email.withdraw', $transport->records[1]->topic());
        self::assertSame('wd-2', $transport->records[1]->key());
        self::assertStringContainsString('"recipient":"notify@example.test"', $transport->records[1]->payload());
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

final class SpyKafkaProducerTransport implements KafkaProducerTransport
{
    /** @var list<KafkaProducerRecord> */
    public array $records = [];

    public function publish(KafkaProducerRecord $record): void
    {
        $this->records[] = $record;
    }
}

final class NullStructuredLogger implements StructuredLogger
{
    public function emergency(string $message, LogContext $context): void
    {
    }

    public function alert(string $message, LogContext $context): void
    {
    }

    public function critical(string $message, LogContext $context): void
    {
    }

    public function error(string $message, LogContext $context): void
    {
    }

    public function warning(string $message, LogContext $context): void
    {
    }

    public function notice(string $message, LogContext $context): void
    {
    }

    public function info(string $message, LogContext $context): void
    {
    }

    public function debug(string $message, LogContext $context): void
    {
    }
}
