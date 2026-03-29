<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionDirection;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidAccountTransaction extends DomainException
{
    public static function emptyField(string $field): self
    {
        return new self(
            message: sprintf('Account transaction %s cannot be empty.', $field),
            errorCode: ErrorCode::ACCOUNT_TRANSACTION_EMPTY_FIELD,
            context: ['field' => $field]
        );
    }

    public static function zeroAmount(): self
    {
        return new self(
            message: 'Account transaction amount must be greater than zero.',
            errorCode: ErrorCode::ACCOUNT_TRANSACTION_ZERO_AMOUNT,
            context: []
        );
    }

    public static function inconsistentBalanceFlow(
        AccountTransactionDirection $direction,
        AccountTransactionReferenceType $referenceType,
        Money $amount,
        Money $balanceBefore,
        Money $balanceAfter,
    ): self {
        return new self(
            message: 'Account transaction balance flow is inconsistent.',
            errorCode: ErrorCode::ACCOUNT_TRANSACTION_INCONSISTENT_BALANCE_FLOW,
            context: [
                'direction' => $direction->value,
                'reference_type' => $referenceType->value,
                'amount' => $amount->toDecimal(),
                'balance_before' => $balanceBefore->toDecimal(),
                'balance_after' => $balanceAfter->toDecimal(),
            ]
        );
    }
}
