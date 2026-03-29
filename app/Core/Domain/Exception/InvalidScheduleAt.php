<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidScheduleAt extends InvalidScheduleException
{
    public static function invalidFormat(string $value): self
    {
        return new self(
            message: 'Schedule date format is invalid.',
            errorCode: ErrorCode::SCHEDULE_AT_INVALID_FORMAT,
            context: ['value' => $value]
        );
    }

    public static function pastDate(string $value, string $currentTime): self
    {
        return new self(
            message: 'Schedule date cannot be in the past.',
            errorCode: ErrorCode::SCHEDULE_AT_PAST_DATE,
            context: [
                'value' => $value,
                'current_time' => $currentTime,
            ]
        );
    }
}
