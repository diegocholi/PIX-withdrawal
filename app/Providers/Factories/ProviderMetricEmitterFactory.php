<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Psr\Log\LoggerInterface;
use Tecnofit\PixWithdrawal\Providers\Metrics\ProviderMetricEmitter;

final readonly class ProviderMetricEmitterFactory
{
    public function __construct(private LoggerInterface $logger)
    {
    }

    public function create(): ProviderMetricEmitter
    {
        return new ProviderMetricEmitter($this->logger);
    }
}
