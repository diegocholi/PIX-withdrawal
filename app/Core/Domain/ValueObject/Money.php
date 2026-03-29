<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\ValueObject;

use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidMoney;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class Money implements SerializableDto
{
    private function __construct(private int $minorAmount)
    {
        if ($minorAmount < 0) {
            throw InvalidMoney::negativeAmount((string) $minorAmount);
        }
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function fromDecimal(string $amount): self
    {
        $normalizedAmount = trim($amount);

        if (str_starts_with($normalizedAmount, '-')) {
            throw InvalidMoney::negativeAmount($amount);
        }

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $normalizedAmount)) {
            throw InvalidMoney::invalidFormat($amount);
        }

        [$wholePart, $fractionPart] = array_pad(explode('.', $normalizedAmount, 2), 2, '0');
        $fractionPart = str_pad($fractionPart, 2, '0');

        return new self(((int) $wholePart * 100) + (int) $fractionPart);
    }

    public static function fromMinorAmount(int $minorAmount): self
    {
        return new self($minorAmount);
    }

    public function add(self $other): self
    {
        return new self($this->minorAmount + $other->minorAmount());
    }

    public function subtract(self $other): self
    {
        $result = $this->minorAmount - $other->minorAmount();

        if ($result < 0) {
            throw InvalidMoney::insufficientAmount($this->toDecimal(), $other->toDecimal());
        }

        return new self($result);
    }

    public function equals(self $other): bool
    {
        return $this->minorAmount === $other->minorAmount();
    }

    public function greaterThan(self $other): bool
    {
        return $this->minorAmount > $other->minorAmount();
    }

    public function greaterThanOrEqual(self $other): bool
    {
        return $this->minorAmount >= $other->minorAmount();
    }

    public function lessThan(self $other): bool
    {
        return $this->minorAmount < $other->minorAmount();
    }

    public function isZero(): bool
    {
        return $this->minorAmount === 0;
    }

    public function minorAmount(): int
    {
        return $this->minorAmount;
    }

    public function toDecimal(): string
    {
        $wholePart = intdiv($this->minorAmount, 100);
        $fractionPart = $this->minorAmount % 100;

        return sprintf('%d.%02d', $wholePart, $fractionPart);
    }

    public function toArray(): array
    {
        return [
            'amount' => $this->toDecimal(),
            'minor_amount' => $this->minorAmount(),
        ];
    }

    public function __toString(): string
    {
        return $this->toDecimal();
    }
}
