<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Account;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

final class DomainAtomicAccountDebit implements AtomicAccountDebit
{
    public function execute(Account $account, Money $amount, DateTimeImmutable $processedAt): Result
    {
        return $account->debit($amount, $processedAt);
    }
}
