<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final readonly class MetricPoint implements SerializableDto
{
    /**
     * @param array<string, scalar> $tags
     */
    public function __construct(
        private MetricName $name,
        private int|float $value = 1,
        private array $tags = [],
    ) {
    }

    public function name(): MetricName
    {
        return $this->name;
    }

    public function value(): int|float
    {
        return $this->value;
    }

    /**
     * @return array<string, scalar>
     */
    public function tags(): array
    {
        return $this->tags;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name()->value,
            'value' => $this->value(),
            'tags' => $this->tags(),
        ];
    }
}
