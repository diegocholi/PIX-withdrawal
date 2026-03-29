<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;

final readonly class MetricEmitterFactory
{
    public function __construct(private Observability $observability)
    {
    }

    public function create(): MetricEmitter
    {
        return $this->observability;
    }
}
