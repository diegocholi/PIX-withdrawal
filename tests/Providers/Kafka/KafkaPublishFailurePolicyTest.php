<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaProducerRecord;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishException;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaPublishFailurePolicy;

final class KafkaPublishFailurePolicyTest extends TestCase
{
    public function testLogsRetryablePublishFailureAndWrapsItAsTransientInfrastructureException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('warning')
            ->with(
                'kafka.publish.failed.retryable',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'kafka.publish.transient_failure'
                        && $context->context()['topic'] === 'withdraw.process'
                        && $context->context()['event_name'] === 'withdraw.created'
                        && $context->context()['retryable'] === true;
                }),
            );
        $logger->expects(self::never())->method('error');

        $policy = new KafkaPublishFailurePolicy($logger);

        $this->expectException(TransientInfrastructureException::class);
        $this->expectExceptionMessage('Kafka message publication failed due to a transient broker error.');

        $policy->handle(
            $this->record(),
            KafkaPublishException::flushFailed('withdraw.process', 5),
        );
    }

    public function testLogsNonRetryableFailureAndPropagatesOriginalException(): void
    {
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::never())->method('warning');
        $logger->expects(self::once())
            ->method('error')
            ->with(
                'kafka.publish.failed',
                self::callback(static function (LogContext $context): bool {
                    return $context->toArray()['error_code'] === 'kafka.publish.failed'
                        && $context->context()['retryable'] === false;
                }),
            );

        $policy = new KafkaPublishFailurePolicy($logger);
        $exception = new \RuntimeException('invalid topic configuration');

        $this->expectExceptionObject($exception);

        $policy->handle($this->record(), $exception);
    }

    private function record(): KafkaProducerRecord
    {
        return new KafkaProducerRecord(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.created","correlation_id":"corr-1","occurred_at":"2026-03-28T10:00:00-03:00"}',
            key: 'account-1',
            headers: [
                'correlation_id' => 'corr-1',
                'event_name' => 'withdraw.created',
                'occurred_at' => '2026-03-28T10:00:00-03:00',
            ],
        );
    }
}
