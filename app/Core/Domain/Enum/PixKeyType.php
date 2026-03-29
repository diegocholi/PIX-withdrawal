<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

enum PixKeyType: string
{
    case EMAIL = 'EMAIL';
    case CPF = 'CPF';
    case PHONE = 'PHONE';
    case RANDOM = 'RANDOM';

    public function isEnabled(): bool
    {
        return $this === self::EMAIL;
    }

    public function isSupportedBy(WithdrawMethod $withdrawMethod): bool
    {
        return $withdrawMethod->supportsPixKeyType($this);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $type): string => $type->value,
            self::cases()
        );
    }
}
