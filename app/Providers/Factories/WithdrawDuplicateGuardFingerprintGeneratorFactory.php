<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\DeterministicWithdrawDuplicateGuardFingerprintGenerator;

final readonly class WithdrawDuplicateGuardFingerprintGeneratorFactory
{
    public function create(): WithdrawDuplicateGuardFingerprintGenerator
    {
        return new DeterministicWithdrawDuplicateGuardFingerprintGenerator();
    }
}
