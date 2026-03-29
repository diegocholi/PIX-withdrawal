<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\ValueObject;

use DateTimeImmutable;
use DateTimeZone;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final class ScheduleAtTest extends TestCase
{
    public function testScheduleAtNormalizesDateAgainstClockTimezone(): void
    {
        $clock = $this->clockAt('2026-03-28T10:00:00-03:00');

        $scheduleAt = ScheduleAt::fromString('2026-03-28T13:30:00+00:00', $clock);

        self::assertTrue((new ReflectionClass($scheduleAt))->isReadOnly());
        self::assertInstanceOf(SerializableDto::class, $scheduleAt);
        self::assertSame('2026-03-28T10:30:00-03:00', $scheduleAt->value()->format(DATE_ATOM));
        self::assertSame('2026-03-28T10:30:00-03:00', (string) $scheduleAt);
        self::assertSame(
            ['schedule_at' => '2026-03-28T10:30:00-03:00'],
            $scheduleAt->toArray()
        );
    }

    public function testScheduleAtSupportsComparisonAgainstClockAndOtherSchedules(): void
    {
        $clock = $this->clockAt('2026-03-28T10:00:00-03:00');
        $dueSchedule = ScheduleAt::fromString('2026-03-28T10:00:00-03:00', $clock);
        $futureSchedule = ScheduleAt::fromString('2026-03-28T11:00:00-03:00', $clock);

        self::assertTrue($dueSchedule->isDue($clock));
        self::assertFalse($futureSchedule->isDue($clock));
        self::assertTrue($futureSchedule->isAfter($dueSchedule));
        self::assertTrue($futureSchedule->equals(
            ScheduleAt::fromDateTime(new DateTimeImmutable('2026-03-28T11:00:00-03:00'), $clock)
        ));
    }

    public function testScheduleAtRejectsInvalidDateFormat(): void
    {
        $this->expectException(InvalidScheduleAt::class);
        $this->expectExceptionMessage('Schedule date format is invalid.');

        ScheduleAt::fromString('not-a-date', $this->clockAt('2026-03-28T10:00:00-03:00'));
    }

    public function testScheduleAtRejectsPastDate(): void
    {
        $this->expectException(InvalidScheduleAt::class);
        $this->expectExceptionMessage('Schedule date cannot be in the past.');

        ScheduleAt::fromString('2026-03-28T09:59:59-03:00', $this->clockAt('2026-03-28T10:00:00-03:00'));
    }

    private function clockAt(string $now): Clock
    {
        $currentTime = new DateTimeImmutable($now);
        $timezone = $currentTime->getTimezone();

        return new class ($currentTime, $timezone) implements Clock {
            public function __construct(
                private readonly DateTimeImmutable $currentTime,
                private readonly DateTimeZone $timezone,
            ) {
            }

            public function now(): DateTimeImmutable
            {
                return $this->currentTime->setTimezone($this->timezone);
            }
        };
    }
}
