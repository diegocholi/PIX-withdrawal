<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidPixKey extends InvalidPixKeyException
{
    public static function unsupportedType(PixKeyType $type): self
    {
        return new self(
            message: sprintf('Pix key type "%s" is not enabled yet.', $type->value),
            errorCode: ErrorCode::PIX_KEY_UNSUPPORTED_TYPE,
            context: ['type' => $type->value]
        );
    }

    public static function invalidEmail(string $value): self
    {
        return new self(
            message: 'Pix email key format is invalid.',
            errorCode: ErrorCode::PIX_KEY_INVALID_EMAIL,
            context: ['value' => $value]
        );
    }
}
