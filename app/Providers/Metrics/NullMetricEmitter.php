<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Metrics;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class NullMetricEmitter implements MetricEmitter
{
    public function increment(MetricPoint $metricPoint): void
    {
    }
}
