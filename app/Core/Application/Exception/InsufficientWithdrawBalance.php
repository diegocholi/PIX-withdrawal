<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\Account;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InsufficientWithdrawBalance extends ApplicationException
{
    public static function forImmediateWithdraw(Account $account, Money $requestedAmount): self
    {
        return new self(
            message: 'Account balance is insufficient for immediate withdraw creation.',
            errorCode: ErrorCode::WITHDRAW_INSUFFICIENT_FUNDS,
            context: [
                'account_id' => $account->id(),
                'requested_amount' => $requestedAmount->toDecimal(),
            ],
        );
    }
}
