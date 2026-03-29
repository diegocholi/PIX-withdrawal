<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;

final class MoneyRecordMapper
{
    public function toRecord(Money $money): string
    {
        return $money->toDecimal();
    }

    public function toDomain(mixed $value, string $field): Money
    {
        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw new \InvalidArgumentException(sprintf('Record field "%s" must be a non-empty money decimal.', $field));
        }

        return Money::fromDecimal($normalizedValue);
    }
}
