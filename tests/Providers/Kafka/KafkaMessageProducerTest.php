<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageProducer;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerRecord;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishException;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerTransport;

final class KafkaMessageProducerTest extends TestCase
{
    public function testPublishesRecordThroughTransportAndLogsOperationalContext(): void
    {
        $record = new KafkaProducerRecord(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00","trace_metadata":{"cli_correlation_id":"corr-cli-1"}}',
            key: 'account-1',
            headers: [
                'correlation_id' => 'corr-1',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ]
        );

        $transport = $this->createMock(KafkaProducerTransport::class);
        $transport->expects(self::once())
            ->method('publish')
            ->with($record);

        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'kafka.publish.attempt',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray() === [
                        'correlation_id' => 'corr-1',
                        'withdraw_id' => null,
                        'account_id' => null,
                        'status' => null,
                        'error_code' => null,
                        'trace_metadata' => [
                            'cli_correlation_id' => 'corr-cli-1',
                        ],
                        'context' => [
                            'provider' => 'kafka',
                            'operation' => 'kafka.publish',
                            'topic' => 'withdraw.process',
                            'partition' => RD_KAFKA_PARTITION_UA,
                            'event_name' => 'withdraw.created',
                        ],
                    ];
                })
            );

        (new KafkaMessageProducer($transport, $logger, new KafkaPublishFailurePolicy($logger)))->publish($record);
    }

    public function testDelegatesRetryableTransportFailureToFailurePolicy(): void
    {
        $record = new KafkaProducerRecord(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'account-1',
            headers: [
                'correlation_id' => 'corr-1',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ]
        );

        $transport = $this->createMock(KafkaProducerTransport::class);
        $transport->expects(self::once())
            ->method('publish')
            ->with($record)
            ->willThrowException(KafkaPublishException::flushFailed('withdraw.process', 5));

        $logger = new SpyKafkaStructuredLogger();

        $this->expectException(\Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException::class);
        $this->expectExceptionMessage('Kafka message publication failed due to a transient broker error.');

        try {
            (new KafkaMessageProducer($transport, $logger, new KafkaPublishFailurePolicy($logger)))->publish($record);
        } finally {
            self::assertSame(['kafka.publish.attempt'], $logger->infoMessages());
            self::assertSame(['kafka.publish.failed.retryable'], $logger->warningMessages());
        }
    }
}

final class SpyKafkaStructuredLogger implements StructuredLogger
{
    /** @var list<string> */
    private array $infoMessages = [];

    /** @var list<string> */
    private array $warningMessages = [];

    /** @var list<string> */
    private array $errorMessages = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoMessages[] = $message;
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->warningMessages[] = $message;
    }

    public function error(string $message, LogContext $context): void
    {
        $this->errorMessages[] = $message;
    }

    /**
     * @return list<string>
     */
    public function infoMessages(): array
    {
        return $this->infoMessages;
    }

    /**
     * @return list<string>
     */
    public function warningMessages(): array
    {
        return $this->warningMessages;
    }

    /**
     * @return list<string>
     */
    public function errorMessages(): array
    {
        return $this->errorMessages;
    }

    public function emergency(string $message, LogContext $context): void
    {
    }

    public function alert(string $message, LogContext $context): void
    {
    }

    public function critical(string $message, LogContext $context): void
    {
    }

    public function notice(string $message, LogContext $context): void
    {
    }

    public function debug(string $message, LogContext $context): void
    {
    }
}
