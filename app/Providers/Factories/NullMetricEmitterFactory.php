<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Providers\Metrics\NullMetricEmitter;

final class NullMetricEmitterFactory
{
    public function create(): NullMetricEmitter
    {
        return new NullMetricEmitter();
    }
}
