<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Repository;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;

interface WithdrawRepository
{
    public function findById(string $withdrawId): ?AccountWithdraw;

    public function findByIdempotencyKey(string $idempotencyKey): ?AccountWithdraw;

    public function findMostRecentEquivalentSince(
        string $accountId,
        WithdrawMethod $method,
        Money $amount,
        PixKey $pixKey,
        ?ScheduleAt $scheduleAt,
        \DateTimeImmutable $since,
    ): ?AccountWithdraw;

    public function lockById(string $withdrawId): ?AccountWithdraw;

    public function save(AccountWithdraw $withdraw): void;
}
