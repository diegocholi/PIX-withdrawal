<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;

interface WithdrawDuplicateGuardFingerprintGenerator
{
    public function generate(ValidatedCreateWithdrawData $data): string;
}
