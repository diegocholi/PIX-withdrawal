<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;
use Tecnofit\PixWithdrawal\Providers\Factories\MetricEmitterFactory;

final class MetricEmitterFactoryTest extends TestCase
{
    public function testReusesObservabilityInstanceAsMetricEmitter(): void
    {
        $observability = new NullObservability();
        $factory = new MetricEmitterFactory($observability);

        self::assertSame($observability, $factory->create());
    }
}
