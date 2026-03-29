<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class WithdrawNotProcessable extends CoreProcessingException
{
    public static function withStatus(string $withdrawId, WithdrawStatus $status): self
    {
        return new self(
            message: 'Withdraw is not eligible for processing.',
            errorCode: ErrorCode::WITHDRAW_NOT_PROCESSABLE,
            context: [
                'withdraw_id' => $withdrawId,
                'status' => $status->value,
            ]
        );
    }
}
