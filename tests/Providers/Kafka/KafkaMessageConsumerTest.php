<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerHandler;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeException;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerSignalListener;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerTransport;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaMessageConsumer;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaPayloadContract;

final class KafkaMessageConsumerTest extends TestCase
{
    public function testConsumesMessagesFromTransportAndDelegatesToHandler(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: ['correlation_id' => 'corr-1'],
            partition: 2,
            offset: 9,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::exactly(2))
            ->method('consume')
            ->with(1000)
            ->willReturnOnConsecutiveCalls(null, $message);
        $transport->expects(self::once())
            ->method('commit')
            ->with($message);

        $handler = new class implements KafkaConsumerHandler {
            public ?KafkaConsumerMessage $receivedMessage = null;

            public function handle(KafkaConsumerMessage $message): void
            {
                $this->receivedMessage = $message;
            }
        };

        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::exactly(3))
            ->method('info')
            ->with(
                self::logicalOr(
                    self::equalTo('kafka.consume.started'),
                    self::equalTo('kafka.consume.received'),
                    self::equalTo('kafka.consume.stopped'),
                ),
                self::isInstanceOf(LogContext::class),
            );

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );

        self::assertSame(1, $consumer->consume($handler, 1));
        self::assertSame($message, $handler->receivedMessage);
    }

    public function testStopsConsumingWhenStopIsRequestedByHandler(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-2","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);
        $transport->expects(self::once())
            ->method('commit')
            ->with($message);

        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::exactly(3))->method('info');

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );
        $handler = new class ($consumer) implements KafkaConsumerHandler {
            public function __construct(private KafkaMessageConsumer $consumer)
            {
            }

            public function handle(KafkaConsumerMessage $message): void
            {
                $this->consumer->requestStop();
            }
        };

        self::assertSame(1, $consumer->consume($handler));
    }

    public function testDoesNotCommitWhenHandlerFails(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-3","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);
        $transport->expects(self::never())
            ->method('commit');

        $logger = new SpyKafkaConsumerLogger();

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );
        $handler = new class implements KafkaConsumerHandler {
            public function handle(KafkaConsumerMessage $message): void
            {
                throw new \RuntimeException('boom');
            }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('boom');

        try {
            $consumer->consume($handler, 1);
        } finally {
            self::assertSame(['kafka.consume.started', 'kafka.consume.received'], $logger->infoMessages());
            self::assertSame(['kafka.consume.failed'], $logger->errorMessages());
        }
    }

    public function testLogsInvalidKafkaMessageBeforePropagatingFailure(): void
    {
        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willThrowException(new \Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaMessage(
                'Kafka message must define required metadata "correlation_id".'
            ));
        $transport->expects(self::never())
            ->method('commit');

        $logger = new SpyKafkaConsumerLogger();

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );
        $handler = new class implements KafkaConsumerHandler {
            public function handle(KafkaConsumerMessage $message): void
            {
            }
        };

        $this->expectException(\Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaMessage::class);

        try {
            $consumer->consume($handler, 1);
        } finally {
            self::assertSame(['kafka.consume.started'], $logger->infoMessages());
            self::assertSame(['kafka.consume.invalid_message'], $logger->errorMessages());
        }
    }

    public function testDoesNotCommitAndLogsInvalidPayloadFailure(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-4","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);
        $transport->expects(self::never())
            ->method('commit');

        $logger = new SpyKafkaConsumerLogger();

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );
        $handler = new class implements KafkaConsumerHandler {
            public function handle(KafkaConsumerMessage $message): void
            {
                throw InvalidKafkaPayloadContract::missingField('payload.event', 'notification.email.withdraw');
            }
        };

        $this->expectException(InvalidKafkaPayloadContract::class);

        try {
            $consumer->consume($handler, 1);
        } finally {
            self::assertSame(['kafka.consume.started', 'kafka.consume.received'], $logger->infoMessages());
            self::assertSame(['kafka.consume.invalid_payload'], $logger->errorMessages());
        }
    }

    public function testWrapsRetryableCommitFailureAsTransientInfrastructureException(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-5","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);
        $transport->expects(self::once())
            ->method('commit')
            ->with($message)
            ->willThrowException(KafkaConsumeException::commitFailed($message, new \RuntimeException('broker unavailable')));

        $logger = new SpyKafkaConsumerLogger();

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );
        $handler = new class implements KafkaConsumerHandler {
            public function handle(KafkaConsumerMessage $message): void
            {
            }
        };

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('Kafka message consumption failed due to a transient broker error.');

        try {
            $consumer->consume($handler, 1);
        } finally {
            self::assertSame(['kafka.consume.started', 'kafka.consume.received'], $logger->infoMessages());
            self::assertSame(['kafka.consume.failed.retryable'], $logger->warningMessages());
        }
    }

    public function testRegistersSignalListenerAndStopsLoopWhenSignalIsTriggered(): void
    {
        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::never())
            ->method('commit');

        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::exactly(2))->method('info');

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            new class ($transport) implements KafkaConsumerSignalListener {
                public function __construct(private KafkaConsumerTransport $transport)
                {
                }

                public function listen(\Closure $stopConsumer): void
                {
                    $this->transport
                        ->expects(TestCase::never())
                        ->method('consume');

                    $stopConsumer();
                }
            },
            new KafkaConsumeFailurePolicy($logger),
        );

        $handler = new class implements KafkaConsumerHandler {
            public function handle(KafkaConsumerMessage $message): void
            {
            }
        };

        self::assertSame(0, $consumer->consume($handler));
        self::assertTrue($consumer->wasInterrupted());
    }

    public function testManualStopDoesNotMarkConsumerAsInterruptedBySignal(): void
    {
        $message = new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-6","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        );

        $transport = $this->createMock(KafkaConsumerTransport::class);
        $transport->expects(self::once())
            ->method('subscribe')
            ->with(['withdraw.process']);
        $transport->expects(self::once())
            ->method('consume')
            ->with(1000)
            ->willReturn($message);
        $transport->expects(self::once())
            ->method('commit')
            ->with($message);

        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::exactly(3))->method('info');

        $consumer = new KafkaMessageConsumer(
            $transport,
            $logger,
            'withdraw.process',
            1000,
            null,
            new KafkaConsumeFailurePolicy($logger),
        );

        $handler = new class ($consumer) implements KafkaConsumerHandler {
            public function __construct(private KafkaMessageConsumer $consumer)
            {
            }

            public function handle(KafkaConsumerMessage $message): void
            {
                $this->consumer->requestStop();
            }
        };

        self::assertSame(1, $consumer->consume($handler));
        self::assertFalse($consumer->wasInterrupted());
    }
}

final class SpyKafkaConsumerLogger implements StructuredLogger
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
