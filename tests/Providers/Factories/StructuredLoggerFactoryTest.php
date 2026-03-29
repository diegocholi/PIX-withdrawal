<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;
use Tecnofit\PixWithdrawal\Providers\Factories\StructuredLoggerFactory;

final class StructuredLoggerFactoryTest extends TestCase
{
    public function testReusesObservabilityInstanceAsStructuredLogger(): void
    {
        $observability = new NullObservability();
        $factory = new StructuredLoggerFactory($observability);

        self::assertSame($observability, $factory->create());
    }
}
