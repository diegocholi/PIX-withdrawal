<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Mappers;

use DateTimeImmutable;
use DateTimeInterface;

final class DateTimeRecordMapper
{
    public function toRecord(DateTimeImmutable $value): string
    {
        return $value->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s.u');
    }

    public function toNullableRecord(?DateTimeImmutable $value): ?string
    {
        return $value !== null ? $this->toRecord($value) : null;
    }

    public function toDomain(mixed $value, string $field): DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return new DateTimeImmutable($value->format(DATE_ATOM));
        }

        $normalizedValue = trim((string) $value);

        if ($normalizedValue === '') {
            throw new \InvalidArgumentException(sprintf('Record field "%s" must be a non-empty datetime.', $field));
        }

        $parsedValue = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s.u',
            $normalizedValue,
            new \DateTimeZone('UTC')
        );

        if ($parsedValue instanceof DateTimeImmutable) {
            return $parsedValue;
        }

        return new DateTimeImmutable($normalizedValue, new \DateTimeZone('UTC'));
    }

    public function toNullableDomain(mixed $value, string $field): ?DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        if (is_string($value) && trim($value) === '') {
            return null;
        }

        return $this->toDomain($value, $field);
    }
}
