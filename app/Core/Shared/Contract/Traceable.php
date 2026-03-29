<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

interface Traceable
{
    public function correlationId(): string;

    /**
     * @return array<string, mixed>
     */
    public function traceMetadata(): array;
}
