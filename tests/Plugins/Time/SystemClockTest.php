<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Time;

use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Time\SystemClock;

final class SystemClockTest extends TestCase
{
    public function testNowUsesConfiguredTimezoneAndSharedContract(): void
    {
        $clock = new SystemClock(new DateTimeZone('America/Sao_Paulo'));
        $now = $clock->now();

        self::assertInstanceOf(Clock::class, $clock);
        self::assertInstanceOf(ClockInterface::class, $clock);
        self::assertSame('America/Sao_Paulo', $now->getTimezone()->getName());
    }
}
