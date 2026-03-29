<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Observability;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class NullObservability implements Observability
{
    public function info(string $message, LogContext $context): void
    {
    }

    public function warning(string $message, LogContext $context): void
    {
    }

    public function error(string $message, LogContext $context): void
    {
    }

    public function increment(MetricPoint $metricPoint): void
    {
    }
}
