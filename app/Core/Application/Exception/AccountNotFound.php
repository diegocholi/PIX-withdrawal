<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class AccountNotFound extends ApplicationException
{
    public static function withId(string $accountId): self
    {
        return new self(
            message: 'Account was not found for withdraw creation.',
            errorCode: ErrorCode::ACCOUNT_NOT_FOUND,
            context: ['account_id' => $accountId]
        );
    }
}
