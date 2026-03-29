<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Contract;

interface SensitiveDataMasker
{
    public function maskPixKey(?string $value): ?string;

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function maskPayload(array $payload): array;
}
