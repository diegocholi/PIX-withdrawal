<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

enum WithdrawStatus: string
{
    case PENDING = 'PENDING';
    case SCHEDULED = 'SCHEDULED';
    case QUEUED = 'QUEUED';
    case PROCESSING = 'PROCESSING';
    case DONE = 'DONE';
    case FAILED_INSUFFICIENT_FUNDS = 'FAILED_INSUFFICIENT_FUNDS';
    case FAILED_VALIDATION = 'FAILED_VALIDATION';
    case FAILED_INTERNAL = 'FAILED_INTERNAL';

    public function isFinal(): bool
    {
        return in_array($this, self::finalStates(), true);
    }

    public function canBeQueued(): bool
    {
        return in_array($this, [self::PENDING, self::SCHEDULED], true);
    }

    public function canBeProcessed(): bool
    {
        return in_array($this, [self::QUEUED, self::PROCESSING], true);
    }

    /**
     * @return list<self>
     */
    public static function finalStates(): array
    {
        return [
            self::DONE,
            self::FAILED_INSUFFICIENT_FUNDS,
            self::FAILED_VALIDATION,
            self::FAILED_INTERNAL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $status): string => $status->value,
            self::cases()
        );
    }
}
