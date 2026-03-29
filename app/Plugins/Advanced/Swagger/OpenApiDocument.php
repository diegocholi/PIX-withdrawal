<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger;

final readonly class OpenApiDocument
{
    /**
     * @param array<string, mixed> $payload
     */
    public function __construct(
        private array $payload,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->payload;
    }
}
