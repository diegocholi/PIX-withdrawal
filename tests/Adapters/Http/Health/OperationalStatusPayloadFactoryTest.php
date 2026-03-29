<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Health;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Health\OperationalStatusPayloadFactory;

final class OperationalStatusPayloadFactoryTest extends TestCase
{
    public function testHealthPayloadMatchesOperationalContract(): void
    {
        $factory = new OperationalStatusPayloadFactory();

        self::assertSame(
            [
                'status' => 'ok',
                'check' => 'health',
                'runtime' => 'http',
            ],
            $factory->health()
        );
    }

    public function testReadinessPayloadMatchesOperationalContract(): void
    {
        $factory = new OperationalStatusPayloadFactory();

        self::assertSame(
            [
                'status' => 'ready',
                'check' => 'readiness',
                'runtime' => 'http',
            ],
            $factory->readiness(true)
        );
        self::assertSame(
            [
                'status' => 'not_ready',
                'check' => 'readiness',
                'runtime' => 'http',
            ],
            $factory->readiness(false)
        );
    }
}
