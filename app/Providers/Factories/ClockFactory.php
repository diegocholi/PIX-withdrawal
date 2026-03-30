<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Plugins\Time\SystemClock;
use Tecnofit\PixWithdrawal\Plugins\Time\TestClock;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class ClockFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private \DateTimeZone $dateTimeZone,
    ) {
    }

    public function create(): Clock
    {
        $driver = $this->providerConfigProvider->clockDriver();

        return match ($driver) {
            'system' => new SystemClock($this->dateTimeZone),
            'test' => new TestClock(null, $this->dateTimeZone),
            default => throw new \InvalidArgumentException(sprintf('Unsupported providers.clock.driver "%s".', $driver)),
        };
    }
}
