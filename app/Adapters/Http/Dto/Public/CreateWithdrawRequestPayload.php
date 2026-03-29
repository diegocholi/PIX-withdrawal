<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public;

final readonly class CreateWithdrawRequestPayload
{
    public function __construct(
        private string $amount,
        private string $method,
        private CreateWithdrawPixRequestPayload $pix,
        private ?CreateWithdrawScheduleRequestPayload $schedule = null,
    ) {
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->amount,
            'method' => $this->method,
            'pix' => $this->pix->toArray(),
            'schedule' => $this->schedule?->toArray(),
        ];
    }
}
