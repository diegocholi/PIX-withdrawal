<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeLogPayloadSerializer;

final readonly class LogPayloadSerializerFactory
{
    public function __construct(
        private SensitiveDataMasker $sensitiveDataMasker,
        private ProviderPayloadNormalizer $payloadNormalizer,
    )
    {
    }

    public function create(): LogPayloadSerializer
    {
        return new SafeLogPayloadSerializer($this->sensitiveDataMasker, $this->payloadNormalizer);
    }
}
