<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Metrics;

use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final readonly class ProviderMetricEmitter implements MetricEmitter
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function increment(MetricPoint $metricPoint): void
    {
        $this->logger->info('metric.incremented', [
            'metric_name' => $metricPoint->name()->value,
            'metric_value' => $metricPoint->value(),
            'metric_tags' => $metricPoint->tags(),
        ]);
    }
}
