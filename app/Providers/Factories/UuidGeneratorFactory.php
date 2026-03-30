<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\FakeUuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomUuidGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class UuidGeneratorFactory
{
    public function __construct(private ProviderConfigProvider $providerConfigProvider)
    {
    }

    public function create(): UuidGenerator
    {
        $driver = $this->providerConfigProvider->uuidDriver();

        return match ($driver) {
            'random' => new RandomUuidGenerator(),
            'fake' => new FakeUuidGenerator(),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.identifiers.uuid_driver "%s".',
                $driver
            )),
        };
    }
}
