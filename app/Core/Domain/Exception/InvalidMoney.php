<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidMoney extends DomainException
{
    public static function negativeAmount(string $amount): self
    {
        return new self(
            message: 'Money amount cannot be negative.',
            errorCode: ErrorCode::MONEY_NEGATIVE_AMOUNT,
            context: ['amount' => $amount]
        );
    }

    public static function invalidFormat(string $amount): self
    {
        return new self(
            message: 'Money amount format is invalid.',
            errorCode: ErrorCode::MONEY_INVALID_FORMAT,
            context: ['amount' => $amount]
        );
    }

    public static function insufficientAmount(string $currentAmount, string $subtractedAmount): self
    {
        return new self(
            message: 'Money subtraction cannot produce a negative amount.',
            errorCode: ErrorCode::MONEY_INSUFFICIENT_AMOUNT,
            context: [
                'current_amount' => $currentAmount,
                'subtracted_amount' => $subtractedAmount,
            ]
        );
    }
}
