<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Bootstrap;

use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfigProvider;

final class AdvancedPluginBindingRegistry
{
    /**
     * @return array<class-string, class-string|\Closure>
     */
    public static function definitions(): array
    {
        return [
            AdvancedPluginConfig::class => static fn ($container) => $container->get(AdvancedPluginConfigProvider::class)->provide(),
            AdvancedPluginConfigProvider::class => AdvancedPluginConfigProvider::class,
        ];
    }
}
