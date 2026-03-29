<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

interface LogPayloadSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function serialize(LogContext $context): array;
}
