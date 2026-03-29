<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaMessage;
use Tecnofit\PixWithdrawal\Providers\Kafka\InvalidKafkaPayloadContract;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeException;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumeFailurePolicy;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final class KafkaConsumeFailurePolicyTest extends TestCase
{
    public function testLogsRetryablePollFailureAndWrapsItAsTransientInfrastructureException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'kafka.consume.failed.retryable',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'kafka.consume.transient_failure'
                        && $context->context()['topic'] === 'withdraw.process'
                        && $context->context()['retryable'] === true;
                }),
            );
        $logger->expects(self::never())->method('error');

        $policy = new KafkaConsumeFailurePolicy($logger);

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('Kafka message consumption failed due to a transient broker error.');

        $policy->handlePollFailure(
            'withdraw.process',
            KafkaConsumeException::pollingFailed(['withdraw.process'], 5, 'broker unavailable'),
        );
    }

    public function testLogsInvalidPayloadFailureAndPropagatesOriginalException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'kafka.consume.invalid_payload',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'kafka.invalid_payload'
                        && $context->context()['event_name'] === 'withdraw.created'
                        && $context->context()['retryable'] === false;
                }),
            );
        $logger->expects(self::never())->method('warning');

        $policy = new KafkaConsumeFailurePolicy($logger);
        $exception = InvalidKafkaPayloadContract::missingField('payload.event', 'notification.email.withdraw');

        $this->expectExceptionObject($exception);

        $policy->handleMessageFailure($this->message(), $exception);
    }

    public function testLogsInvalidMessageFailureAndPropagatesOriginalException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'kafka.consume.invalid_message',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'kafka.invalid_message'
                        && $context->context()['topic'] === 'withdraw.process';
                }),
            );
        $logger->expects(self::never())->method('warning');

        $policy = new KafkaConsumeFailurePolicy($logger);
        $exception = InvalidKafkaMessage::missingRequiredMetadata('correlation_id');

        $this->expectExceptionObject($exception);

        $policy->handlePollFailure('withdraw.process', $exception);
    }

    private function message(): KafkaConsumerMessage
    {
        return new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'acc-1',
            headers: ['correlation_id' => 'corr-1'],
            partition: 2,
            offset: 9,
        );
    }
}
