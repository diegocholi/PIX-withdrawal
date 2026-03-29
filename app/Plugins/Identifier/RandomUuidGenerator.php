<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Identifier;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;

final class RandomUuidGenerator implements CorrelationIdGenerator, UuidGenerator
{
    public function generate(): string
    {
        $bytes = random_bytes(16);

        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);

        $hexadecimal = bin2hex($bytes);

        return sprintf(
            '%s-%s-%s-%s-%s',
            substr($hexadecimal, 0, 8),
            substr($hexadecimal, 8, 4),
            substr($hexadecimal, 12, 4),
            substr($hexadecimal, 16, 4),
            substr($hexadecimal, 20, 12),
        );
    }
}
