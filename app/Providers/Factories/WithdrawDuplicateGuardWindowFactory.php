<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Providers\Config\ConfiguredWithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class WithdrawDuplicateGuardWindowFactory
{
    public function __construct(private ProviderConfigProvider $providerConfigProvider)
    {
    }

    public function create(): WithdrawDuplicateGuardWindow
    {
        return new ConfiguredWithdrawDuplicateGuardWindow(
            $this->providerConfigProvider->withdrawDuplicateGuardWindowSeconds(),
        );
    }
}
