<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class UuidGeneratorFactory
{
    public function __construct(
        private ProviderConfigProvider $providerConfigProvider,
        private RandomUuidGeneratorFactory $randomUuidGeneratorFactory,
        private FakeUuidGeneratorFactory $fakeUuidGeneratorFactory,
    ) {
    }

    public function create(): UuidGenerator
    {
        $driver = $this->providerConfigProvider->uuidDriver();

        return match ($driver) {
            'random' => $this->randomUuidGeneratorFactory->create(),
            'fake' => $this->fakeUuidGeneratorFactory->create(),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.identifiers.uuid_driver "%s".',
                $driver
            )),
        };
    }
}
