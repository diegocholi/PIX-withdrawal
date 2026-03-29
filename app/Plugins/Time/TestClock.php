<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Time;

use DateInterval;
use DateTimeImmutable;
use DateTimeZone;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class TestClock implements Clock
{
    private DateTimeImmutable $currentTime;

    public function __construct(?DateTimeImmutable $initialTime = null, ?DateTimeZone $timeZone = null)
    {
        $resolvedTimeZone = $timeZone ?? $initialTime?->getTimezone() ?? new DateTimeZone(date_default_timezone_get());
        $resolvedInitialTime = $initialTime ?? new DateTimeImmutable('now', $resolvedTimeZone);

        $this->currentTime = $resolvedInitialTime->setTimezone($resolvedTimeZone);
    }

    public function now(): DateTimeImmutable
    {
        return $this->currentTime;
    }

    public function freezeAt(DateTimeImmutable $dateTime): void
    {
        $this->currentTime = $dateTime->setTimezone($this->currentTime->getTimezone());
    }

    public function advance(DateInterval $interval): void
    {
        $this->currentTime = $this->currentTime->add($interval);
    }
}
