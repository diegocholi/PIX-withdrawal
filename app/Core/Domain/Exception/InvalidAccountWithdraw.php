<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidAccountWithdraw extends DomainException
{
    public static function emptyField(string $field): self
    {
        return new self(
            message: sprintf('Account withdraw %s cannot be empty.', $field),
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_EMPTY_FIELD,
            context: ['field' => $field]
        );
    }

    public static function negativeRetryCount(int $retryCount): self
    {
        return new self(
            message: 'Account withdraw retry_count cannot be negative.',
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_NEGATIVE_RETRY_COUNT,
            context: ['retry_count' => $retryCount]
        );
    }

    /**
     * @param array<string, mixed> $context
     */
    public static function inconsistentState(WithdrawStatus $status, string $reason, array $context = []): self
    {
        return new self(
            message: sprintf('Account withdraw state is inconsistent for status "%s".', $status->value),
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_INCONSISTENT_STATE,
            context: array_merge(
                [
                    'status' => $status->value,
                    'reason' => $reason,
                ],
                $context
            )
        );
    }
}
