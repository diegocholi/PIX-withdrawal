<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

interface SerializableDto
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
