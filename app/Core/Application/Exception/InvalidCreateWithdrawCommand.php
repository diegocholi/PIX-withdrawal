<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidCreateWithdrawCommand extends ApplicationException
{
    public static function emptyField(string $field): self
    {
        return new self(
            message: sprintf('Create withdraw command %s cannot be empty.', $field),
            errorCode: ErrorCode::CREATE_WITHDRAW_COMMAND_EMPTY_FIELD,
            context: ['field' => $field]
        );
    }

    public static function invalidMethod(string $method): self
    {
        return new self(
            message: 'Create withdraw command method is invalid.',
            errorCode: ErrorCode::CREATE_WITHDRAW_COMMAND_INVALID_METHOD,
            context: ['method' => trim($method)]
        );
    }

    public static function invalidPixKeyType(string $pixKeyType): self
    {
        return new self(
            message: 'Create withdraw command pix_key_type is invalid.',
            errorCode: ErrorCode::CREATE_WITHDRAW_COMMAND_INVALID_PIX_KEY_TYPE,
            context: ['pix_key_type' => trim($pixKeyType)]
        );
    }
}
