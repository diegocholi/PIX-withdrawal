<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;

final class NullObservabilityFactory
{
    public function create(): NullObservability
    {
        return new NullObservability();
    }
}
