<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Enum;

enum AccountTransactionReferenceType: string
{
    case WITHDRAW = 'WITHDRAW';

    public function isWithdraw(): bool
    {
        return $this === self::WITHDRAW;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_map(
            static fn (self $referenceType): string => $referenceType->value,
            self::cases()
        );
    }
}
