<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class DuplicateWithdrawRequestBlocked extends ApplicationException
{
    public static function becauseRecentEquivalentRequestExists(
        AccountWithdraw $withdraw,
        int $retryAfterSeconds,
    ): self {
        return new self(
            message: 'An equivalent withdraw request was already accepted recently.',
            errorCode: ErrorCode::WITHDRAW_DUPLICATE_REQUEST_BLOCKED,
            context: [
                'withdraw_id' => $withdraw->id(),
                'retry_after_seconds' => $retryAfterSeconds,
            ],
        );
    }
}
