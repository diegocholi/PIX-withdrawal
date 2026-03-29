<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Observability;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricName;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;

final class NullObservabilityTest extends TestCase
{
    public function testObservabilityCallsAreNoop(): void
    {
        $observability = new NullObservability();
        $context = new LogContext(
            correlationId: 'corr-1',
            withdrawId: 'w-1',
            accountId: 'acc-1',
            status: 'DONE',
            errorCode: 'withdraw.failed',
            traceMetadata: ['trace_id' => 'trace-1'],
            context: ['reason' => 'timeout']
        );

        self::assertInstanceOf(StructuredLogger::class, $observability);
        self::assertInstanceOf(MetricEmitter::class, $observability);
        $observability->info('withdraw.queued', $context);
        $observability->warning('withdraw.retry', $context);
        $observability->error('withdraw.failed', $context);
        $observability->increment(new MetricPoint(MetricName::WITHDRAW_PROCESSED, 1, ['status' => 'done']));

        self::assertTrue(true);
    }
}
