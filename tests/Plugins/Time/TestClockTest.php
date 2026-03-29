<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Time;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Time\TestClock;

final class TestClockTest extends TestCase
{
    public function testFreezeAndAdvanceKeepTimeDeterministic(): void
    {
        $clock = new TestClock(
            new DateTimeImmutable('2026-03-28T10:00:00-03:00'),
            new DateTimeZone('America/Sao_Paulo')
        );

        self::assertInstanceOf(Clock::class, $clock);
        self::assertSame('2026-03-28T10:00:00-03:00', $clock->now()->format(DATE_ATOM));

        $clock->advance(new DateInterval('PT15M'));
        self::assertSame('2026-03-28T10:15:00-03:00', $clock->now()->format(DATE_ATOM));

        $clock->freezeAt(new DateTimeImmutable('2026-03-28T12:30:00+00:00'));
        self::assertSame('2026-03-28T09:30:00-03:00', $clock->now()->format(DATE_ATOM));
    }
}
