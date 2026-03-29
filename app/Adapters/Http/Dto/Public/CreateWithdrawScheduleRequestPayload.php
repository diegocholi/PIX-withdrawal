<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class CreateWithdrawScheduleRequestPayload
{
    public function __construct(private string $at)
    {
    }

    public function toArray(): array
    {
        return [
            'at' => $this->at,
        ];
    }
}
