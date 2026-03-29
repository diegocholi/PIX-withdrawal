<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InsufficientAccountBalance extends InsufficientFundsException
{
    public static function forDebit(string $accountId, Money $currentBalance, Money $debitAmount): self
    {
        return new self(
            message: 'Account balance is insufficient for debit.',
            errorCode: ErrorCode::ACCOUNT_INSUFFICIENT_BALANCE,
            context: [
                'account_id' => $accountId,
                'current_balance' => $currentBalance->toDecimal(),
                'debit_amount' => $debitAmount->toDecimal(),
            ]
        );
    }
}
