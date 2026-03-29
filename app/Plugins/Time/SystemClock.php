<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Time;

use DateTimeImmutable;
use DateTimeZone;
use Psr\Clock\ClockInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class SystemClock implements ClockInterface, Clock
{
    public function __construct(private readonly DateTimeZone $timeZone)
    {
    }

    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable('now', $this->timeZone);
    }
}
