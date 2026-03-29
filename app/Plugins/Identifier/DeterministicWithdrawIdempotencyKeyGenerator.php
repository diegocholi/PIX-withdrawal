<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Identifier;

use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;

final class DeterministicWithdrawIdempotencyKeyGenerator implements WithdrawIdempotencyKeyGenerator
{
    private const PREFIX = 'withdraw:create:v1:';

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

    public function isValid(string $idempotencyKey): bool
    {
        $normalized = trim($idempotencyKey);

        if (!str_starts_with($normalized, self::PREFIX)) {
            return false;
        }

        return preg_match('/^withdraw:create:v1:[0-9a-f]{64}$/', $normalized) === 1;
    }
}
