<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

final class DateTimeZoneFactory
{
    public function create(): \DateTimeZone
    {
        return new \DateTimeZone(date_default_timezone_get());
    }
}
