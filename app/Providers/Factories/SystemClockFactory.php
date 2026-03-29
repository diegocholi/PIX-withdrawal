<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Plugins\Time\SystemClock;

final readonly class SystemClockFactory
{
    public function __construct(private \DateTimeZone $dateTimeZone)
    {
    }

    public function create(): SystemClock
    {
        return new SystemClock($this->dateTimeZone);
    }
}
