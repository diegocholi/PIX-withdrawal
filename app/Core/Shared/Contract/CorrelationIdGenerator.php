<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

interface CorrelationIdGenerator
{
    public function generate(): string;
}
