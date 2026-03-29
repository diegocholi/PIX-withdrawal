<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Service;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class WithdrawDebitTransactionFactory
{
    public function create(
        AccountWithdraw $withdraw,
        Money $balanceBefore,
        Money $balanceAfter,
        DateTimeImmutable $createdAt,
    ): AccountTransaction {
        return AccountTransaction::create(
            accountId: $withdraw->accountId(),
            referenceType: AccountTransactionReferenceType::WITHDRAW,
            referenceId: $withdraw->id(),
            direction: AccountTransactionDirection::DEBIT,
            amount: $withdraw->amount(),
            balanceBefore: $balanceBefore,
            balanceAfter: $balanceAfter,
            createdAt: $createdAt,
        );
    }
}
