<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Metrics;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\MetricName;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;

final class ProviderMetricEmitterTest extends TestCase
{
    public function testIncrementLogsStructuredMetricPayload(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'metric.incremented',
                [
                    'metric_name' => 'withdraw.processed',
                    'metric_value' => 2,
                    'metric_tags' => ['status' => 'done'],
                ]
            );

        $emitter = new ProviderMetricEmitter($logger);

        self::assertInstanceOf(MetricEmitter::class, $emitter);
        $emitter->increment(new MetricPoint(MetricName::WITHDRAW_PROCESSED, 2, ['status' => 'done']));
    }
}
