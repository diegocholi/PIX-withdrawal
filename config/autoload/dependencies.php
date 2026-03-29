<?php

declare(strict_types=1);

use Tecnofit\PixWithdrawal\Core\Application\Bootstrap\ApplicationBindingRegistry;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Bootstrap\AdvancedPluginBindingRegistry;
use Tecnofit\PixWithdrawal\Plugins\Data\Bootstrap\DataBindingRegistry;
use Tecnofit\PixWithdrawal\Providers\Bootstrap\ProviderBindingRegistry;

return array_merge(
    ApplicationBindingRegistry::definitions(),
    AdvancedPluginBindingRegistry::definitions(),
    ProviderBindingRegistry::definitions(),
    DataBindingRegistry::definitions(),
);
