<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Config;

use Hyperf\Contract\ConfigInterface;

final readonly class AdvancedPluginConfigProvider
{
    public function __construct(
        private ConfigInterface $config,
    ) {
    }

    public function provide(): AdvancedPluginConfig
    {
        /** @var array<string, mixed> $advancedConfig */
        $advancedConfig = $this->config->get('advanced', []);
        /** @var array<string, mixed> $openApiConfig */
        $openApiConfig = (array) ($advancedConfig['openapi'] ?? []);
        /** @var array<string, mixed> $swaggerUiConfig */
        $swaggerUiConfig = (array) ($advancedConfig['swagger_ui'] ?? []);

        return new AdvancedPluginConfig(
            openApiTitle: trim((string) ($openApiConfig['title'] ?? 'PIX Withdrawal API')) ?: 'PIX Withdrawal API',
            openApiVersion: trim((string) ($openApiConfig['version'] ?? '1.0.0')) ?: '1.0.0',
            specPath: $this->normalizePath((string) ($openApiConfig['spec_path'] ?? '/openapi.json')),
            swaggerUiPath: $this->normalizePath((string) ($swaggerUiConfig['path'] ?? '/docs')),
            swaggerUiEnabled: (bool) ($swaggerUiConfig['enabled'] ?? false),
        );
    }

    private function normalizePath(string $path): string
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '' || $normalizedPath === '/') {
            return '/';
        }

        return '/' . trim($normalizedPath, '/');
    }
}
