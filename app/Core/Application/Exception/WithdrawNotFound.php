<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class WithdrawNotFound extends ApplicationException
{
    public static function withId(string $withdrawId): self
    {
        return new self(
            message: 'Withdraw was not found for processing.',
            errorCode: ErrorCode::WITHDRAW_NOT_FOUND,
            context: ['withdraw_id' => $withdrawId]
        );
    }
}
