<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Config;

final readonly class AdvancedPluginConfig
{
    public function __construct(
        private string $openApiTitle,
        private string $openApiVersion,
        private string $specPath,
        private string $swaggerUiPath,
        private bool $swaggerUiEnabled,
    ) {
    }

    public function openApiTitle(): string
    {
        return $this->openApiTitle;
    }

    public function openApiVersion(): string
    {
        return $this->openApiVersion;
    }

    public function specPath(): string
    {
        return $this->specPath;
    }

    public function swaggerUiPath(): string
    {
        return $this->swaggerUiPath;
    }

    public function swaggerUiEnabled(): bool
    {
        return $this->swaggerUiEnabled;
    }
}
