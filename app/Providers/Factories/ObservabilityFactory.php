<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;
use Tecnofit\PixWithdrawal\Plugins\Observability\NullObservability;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Logging\HyperfStructuredLogger;
use Tecnofit\PixWithdrawal\Providers\Metrics\NullMetricEmitter;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;

final readonly class ObservabilityFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private HyperfStructuredLogger $hyperfStructuredLogger,
        private ProviderMetricEmitter $providerMetricEmitter,
        private NullMetricEmitter $nullMetricEmitter,
        private NullObservability $nullObservability,
    ) {
    }

    public function create(): Observability
    {
        $driver = $this->providerConfigProvider->observabilityDriver();

        return match ($driver) {
            'hyperf' => new HyperfObservability($this->hyperfStructuredLogger, $this->metricEmitter()),
            'null' => $this->nullObservability,
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.observability.driver "%s".',
                $driver
            )),
        };
    }

    private function metricEmitter(): ProviderMetricEmitter|NullMetricEmitter
    {
        $driver = $this->providerConfigProvider->metricDriver();

        return match ($driver) {
            'logger' => $this->providerMetricEmitter,
            'null' => $this->nullMetricEmitter,
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.metrics.driver "%s".',
                $driver
            )),
        };
    }
}
