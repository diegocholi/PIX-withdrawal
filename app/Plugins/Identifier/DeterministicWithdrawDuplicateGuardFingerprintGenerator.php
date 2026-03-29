<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Identifier;

use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;

final class DeterministicWithdrawDuplicateGuardFingerprintGenerator implements WithdrawDuplicateGuardFingerprintGenerator
{
    private const PREFIX = 'withdraw:guard:v1:';

    public function generate(ValidatedCreateWithdrawData $data): string
    {
        $payload = [
            'account_id' => $data->accountId(),
            'method' => $data->method()->value,
            'amount' => $data->amount()->toDecimal(),
            'pix_key_type' => $data->pixKey()->type()->value,
            'pix_key' => $data->pixKey()->value(),
            'schedule_at' => $data->scheduleAt()?->value()->format(DATE_ATOM),
        ];

        return self::PREFIX . hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
