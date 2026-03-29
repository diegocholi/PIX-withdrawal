<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Hyperf\Contract\ConfigInterface;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class ProviderConfigProviderFactory
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function create(): ProviderConfigProvider
    {
        return new ProviderConfigProvider($this->config);
    }
}
