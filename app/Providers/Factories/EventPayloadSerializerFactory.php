<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Providers\Serialization\ProviderPayloadNormalizer;
use Tecnofit\PixWithdrawal\Providers\Serialization\SafeEventPayloadSerializer;

final readonly class EventPayloadSerializerFactory
{
    public function __construct(
        private SensitiveDataMasker $sensitiveDataMasker,
        private ProviderPayloadNormalizer $payloadNormalizer,
    )
    {
    }

    public function create(): EventPayloadSerializer
    {
        return new SafeEventPayloadSerializer($this->sensitiveDataMasker, $this->payloadNormalizer);
    }
}
