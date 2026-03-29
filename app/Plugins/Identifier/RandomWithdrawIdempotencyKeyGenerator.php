<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Identifier;

use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;

final class RandomWithdrawIdempotencyKeyGenerator implements WithdrawIdempotencyKeyGenerator
{
    private const PREFIX = 'withdraw:req:v1:';

    public function generate(ValidatedCreateWithdrawData $data): string
    {
        return self::PREFIX . bin2hex(random_bytes(32));
    }

    public function isValid(string $idempotencyKey): bool
    {
        return preg_match('/^withdraw:req:v1:[0-9a-f]{64}$/', trim($idempotencyKey)) === 1;
    }
}
