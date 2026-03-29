<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;

final class ProviderPayloadNormalizerFactory
{
    public function create(): ProviderPayloadNormalizer
    {
        return new ProviderPayloadNormalizer();
    }
}
