<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\ValueObject;

use DateTimeImmutable;
use Exception;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class ScheduleAt implements SerializableDto
{
    private function __construct(private DateTimeImmutable $value)
    {
    }

    public static function fromString(string $value, Clock $clock): self
    {
        try {
            $dateTime = new DateTimeImmutable(trim($value));
        } catch (Exception) {
            throw InvalidScheduleAt::invalidFormat($value);
        }

        return self::fromDateTime($dateTime, $clock);
    }

    public static function fromDateTime(DateTimeImmutable $value, Clock $clock): self
    {
        $currentTime = $clock->now();
        $normalizedValue = $value->setTimezone($currentTime->getTimezone());

        if ($normalizedValue < $currentTime) {
            throw InvalidScheduleAt::pastDate(
                $normalizedValue->format(DATE_ATOM),
                $currentTime->format(DATE_ATOM),
            );
        }

        return new self($normalizedValue);
    }

    public function value(): DateTimeImmutable
    {
        return $this->value;
    }

    public function isDue(Clock $clock): bool
    {
        return $this->value <= $clock->now();
    }

    public function isAfter(self $other): bool
    {
        return $this->value > $other->value();
    }

    public function equals(self $other): bool
    {
        return $this->value == $other->value();
    }

    public function toArray(): array
    {
        return [
            'schedule_at' => $this->value()->format(DATE_ATOM),
        ];
    }

    public function __toString(): string
    {
        return $this->value()->format(DATE_ATOM);
    }
}
