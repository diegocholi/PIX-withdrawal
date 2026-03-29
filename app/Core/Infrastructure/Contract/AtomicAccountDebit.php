<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Infrastructure\Contract;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

interface AtomicAccountDebit
{
    public function execute(Account $account, Money $amount, DateTimeImmutable $processedAt): Result;
}
