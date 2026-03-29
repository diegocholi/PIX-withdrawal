<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderSensitiveDataMasker;

final class SensitiveDataMaskerFactory
{
    public function create(): SensitiveDataMasker
    {
        return new ProviderSensitiveDataMasker();
    }
}
