<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidAccountWithdrawPix extends DomainException
{
    public static function emptyWithdrawId(): self
    {
        return new self(
            message: 'Account withdraw pix withdraw_id cannot be empty.',
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_PIX_EMPTY_WITHDRAW_ID,
            context: []
        );
    }

    public static function unsupportedMethod(WithdrawMethod $method): self
    {
        return new self(
            message: sprintf('Account withdraw pix requires a method that supports PIX payload, "%s" given.', $method->value),
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_METHOD,
            context: ['method' => $method->value]
        );
    }

    public static function unsupportedPixKeyType(WithdrawMethod $method, PixKeyType $pixKeyType): self
    {
        return new self(
            message: sprintf(
                'Account withdraw pix does not support key type "%s" for method "%s".',
                $pixKeyType->value,
                $method->value
            ),
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_PIX_UNSUPPORTED_PIX_KEY_TYPE,
            context: [
                'method' => $method->value,
                'pix_key_type' => $pixKeyType->value,
            ]
        );
    }

    public static function inconsistentTimestamps(DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): self
    {
        return new self(
            message: 'Account withdraw pix updated_at cannot be earlier than created_at.',
            errorCode: ErrorCode::ACCOUNT_WITHDRAW_PIX_INCONSISTENT_TIMESTAMPS,
            context: [
                'created_at' => $createdAt->format(DATE_ATOM),
                'updated_at' => $updatedAt->format(DATE_ATOM),
            ]
        );
    }
}
