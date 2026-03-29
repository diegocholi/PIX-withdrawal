<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Identifier;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;

final class FakeUuidGenerator implements UuidGenerator
{
    private int $sequence = 1;

    public function generate(): string
    {
        $uuid = sprintf('00000000-0000-4000-8000-%012d', $this->sequence);
        $this->sequence++;

        return $uuid;
    }
}
