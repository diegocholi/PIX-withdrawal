<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Seeds;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final readonly class SeedScenarioClock implements Clock
{
    public function __construct(private DateTimeImmutable $currentTime)
    {
    }

    public function now(): DateTimeImmutable
    {
        return $this->currentTime;
    }
}
