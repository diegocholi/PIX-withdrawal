<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Plugins\Time\TestClock;

final readonly class TestClockFactory
{
    public function __construct(private \DateTimeZone $dateTimeZone)
    {
    }

    public function create(): TestClock
    {
        return new TestClock(null, $this->dateTimeZone);
    }
}
