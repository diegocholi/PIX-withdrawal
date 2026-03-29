<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class FindWithdrawStatusPixResponsePayload
{
    public function __construct(
        private string $keyType,
        private string $keyMasked,
    ) {
    }

    public function toArray(): array
    {
        return [
            'key_type' => $this->keyType,
            'key_masked' => $this->keyMasked,
        ];
    }
}
