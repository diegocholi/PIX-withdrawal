<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class CorrelationIdGeneratorFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private RandomUuidGeneratorFactory $randomUuidGeneratorFactory,
    ) {
    }

    public function create(): CorrelationIdGenerator
    {
        $driver = $this->providerConfigProvider->correlationIdDriver();

        return match ($driver) {
            'random' => $this->randomUuidGeneratorFactory->create(),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.identifiers.correlation_id_driver "%s".',
                $driver
            )),
        };
    }
}
