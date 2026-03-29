<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Metrics;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\MetricName;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;
use Tecnofit\PixWithdrawal\Providers\Metrics\NullMetricEmitter;

final class NullMetricEmitterTest extends TestCase
{
    public function testIncrementIsSafeNoOp(): void
    {
        $emitter = new NullMetricEmitter();

        self::assertInstanceOf(MetricEmitter::class, $emitter);
        $emitter->increment(new MetricPoint(MetricName::WITHDRAW_PROCESSED, 1, ['status' => 'ignored']));

        self::assertTrue(true);
    }
}
