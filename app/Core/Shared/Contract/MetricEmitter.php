<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

interface MetricEmitter
{
    public function increment(MetricPoint $metricPoint): void;
}
