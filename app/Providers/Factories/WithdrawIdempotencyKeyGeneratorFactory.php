<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomWithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final readonly class WithdrawIdempotencyKeyGeneratorFactory
{
    public function __construct(private ProviderConfigProvider $providerConfigProvider)
    {
    }

    public function create(): WithdrawIdempotencyKeyGenerator
    {
        $driver = $this->providerConfigProvider->withdrawIdempotencyKeyDriver();

        return match ($driver) {
            'random' => new RandomWithdrawIdempotencyKeyGenerator(),
            default => throw new \InvalidArgumentException(sprintf(
                'Unsupported providers.identifiers.withdraw_idempotency_key_driver "%s".',
                $driver
            )),
        };
    }
}
