<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class UnsupportedWithdrawMethodException extends DomainException
{
    public static function withMethod(string $method): self
    {
        return new self(
            message: sprintf('Withdraw method "%s" is not supported.', trim($method)),
            errorCode: ErrorCode::WITHDRAW_UNSUPPORTED_METHOD,
            context: ['method' => trim($method)]
        );
    }
}
