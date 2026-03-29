<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class CreateWithdrawPixRequestPayload
{
    public function __construct(
        private string $keyType,
        private string $key,
    ) {
    }

    public function toArray(): array
    {
        return [
            'key_type' => $this->keyType,
            'key' => $this->key,
        ];
    }
}
