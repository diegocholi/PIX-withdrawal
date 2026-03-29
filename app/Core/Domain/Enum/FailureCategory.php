<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

enum FailureCategory: string
{
    case BUSINESS = 'BUSINESS';
    case VALIDATION = 'VALIDATION';
    case INFRASTRUCTURE_TRANSIENT = 'INFRASTRUCTURE_TRANSIENT';
    case INTERNAL = 'INTERNAL';

    public function isRetryable(): bool
    {
        return $this === self::INFRASTRUCTURE_TRANSIENT;
    }
}
