<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidWithdrawStatusTransition extends InvalidWithdrawStateException
{
    public static function fromTo(WithdrawStatus $currentStatus, WithdrawStatus $targetStatus): self
    {
        return new self(
            message: sprintf(
                'Withdraw status transition from "%s" to "%s" is invalid.',
                $currentStatus->value,
                $targetStatus->value,
            ),
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_INVALID_STATUS_TRANSITION,
            context: [
                'current_status' => $currentStatus->value,
                'target_status' => $targetStatus->value,
            ]
        );
    }
}
