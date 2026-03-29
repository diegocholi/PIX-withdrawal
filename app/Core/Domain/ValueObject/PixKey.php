<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\ValueObject;

use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class PixKey implements SerializableDto
{
    private function __construct(
        private PixKeyType $type,
        private string $value,
    ) {
    }

    public static function from(PixKeyType $type, string $value): self
    {
        $normalizedValue = trim($value);

        if (! $type->isEnabled()) {
            throw InvalidPixKey::unsupportedType($type);
        }

        if ($type === PixKeyType::EMAIL) {
            return self::email($normalizedValue);
        }

        throw InvalidPixKey::unsupportedType($type);
    }

    public static function email(string $value): self
    {
        $normalizedValue = mb_strtolower(trim($value));

        if (filter_var($normalizedValue, FILTER_VALIDATE_EMAIL) === false) {
            throw InvalidPixKey::invalidEmail($value);
        }

        return new self(PixKeyType::EMAIL, $normalizedValue);
    }

    public function type(): PixKeyType
    {
        return $this->type;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isEmail(): bool
    {
        return $this->type === PixKeyType::EMAIL;
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->value === $other->value;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type()->value,
            'value' => $this->value(),
        ];
    }

    public function __toString(): string
    {
        return $this->value();
    }
}
