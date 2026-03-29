<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Observability;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricName;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;

final class HyperfObservabilityTest extends TestCase
{
    public function testInfoDelegatesStructuredPayloadToConcreteLogger(): void
    {
        $structuredLogger = $this->createMock(StructuredLogger::class);
        $metricEmitter = $this->createMock(MetricEmitter::class);
        $structuredLogger->expects(self::once())
            ->method('info')
            ->with(
                'http.request.received',
                self::callback(static fn (LogContext $context): bool => $context->toArray() === [
                    'correlation_id' => 'corr_123',
                    'withdraw_id' => null,
                    'account_id' => 'acc_123',
                    'status' => null,
                    'error_code' => null,
                    'trace_metadata' => [],
                    'context' => [
                        'pix_key' => 'user@example.com',
                        'path' => '/account/acc_123/balance/withdraw',
                    ],
                ])
            );

        $metricEmitter->expects(self::never())->method('increment');
        $observability = new HyperfObservability($structuredLogger, $metricEmitter);

        $observability->info(
            'http.request.received',
            new LogContext(
                correlationId: 'corr_123',
                accountId: 'acc_123',
                context: [
                    'pix_key' => 'user@example.com',
                    'path' => '/account/acc_123/balance/withdraw',
                ]
            )
        );
    }

    public function testErrorDelegatesToConcreteLogger(): void
    {
        $structuredLogger = $this->createMock(StructuredLogger::class);
        $metricEmitter = $this->createMock(MetricEmitter::class);
        $structuredLogger->expects(self::once())
            ->method('error')
            ->with(
                'withdraw.failed',
                self::isInstanceOf(LogContext::class)
            );

        $metricEmitter->expects(self::never())->method('increment');
        $observability = new HyperfObservability($structuredLogger, $metricEmitter);

        $observability->error(
            'withdraw.failed',
            new LogContext(
                correlationId: 'corr_http',
                errorCode: 'withdraw.failed',
                context: ['reason' => 'timeout']
            )
        );
    }

    public function testIncrementDelegatesToConcreteMetricEmitter(): void
    {
        $structuredLogger = $this->createMock(StructuredLogger::class);
        $metricEmitter = $this->createMock(MetricEmitter::class);
        $metricEmitter->expects(self::once())
            ->method('increment')
            ->with(self::callback(
                static fn (MetricPoint $metricPoint): bool => $metricPoint->toArray() === [
                    'name' => 'withdraw.processed',
                    'value' => 1,
                    'tags' => ['status' => 'done'],
                ]
            ));

        $observability = new HyperfObservability($structuredLogger, $metricEmitter);

        $observability->increment(new MetricPoint(MetricName::WITHDRAW_PROCESSED, 1, ['status' => 'done']));
    }
}
