<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Providers\Logging\ProviderLogContextEnricher;

final class ProviderLogContextEnricherFactory
{
    public function create(): ProviderLogContextEnricher
    {
        return new ProviderLogContextEnricher();
    }
}
