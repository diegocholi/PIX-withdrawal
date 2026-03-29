<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Serialization;

use BackedEnum;
use DateTimeInterface;
use Stringable;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use UnitEnum;

final class ProviderPayloadNormalizer
{
    public function normalize(mixed $value): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if ($value instanceof SerializableDto) {
            return $this->normalizeArray($value->toArray());
        }

        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        if ($value instanceof UnitEnum) {
            return $value->name;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return $this->normalizeArray($value);
        }

        return get_debug_type($value);
    }

    /**
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    public function normalizeArray(array $payload): array
    {
        $normalized = [];

        foreach ($payload as $key => $value) {
            $normalized[$key] = $this->normalize($value);
        }

        return $normalized;
    }
}
