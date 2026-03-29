<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Observability;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final readonly class HyperfObservability implements Observability
{
    public function __construct(
        private StructuredLogger $structuredLogger,
        private MetricEmitter $providerMetricEmitter,
    ) {
    }

    public function info(string $message, LogContext $context): void
    {
        $this->structuredLogger->info($message, $context);
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->structuredLogger->warning($message, $context);
    }

    public function error(string $message, LogContext $context): void
    {
        $this->structuredLogger->error($message, $context);
    }

    public function increment(MetricPoint $metricPoint): void
    {
        $this->providerMetricEmitter->increment($metricPoint);
    }
}
