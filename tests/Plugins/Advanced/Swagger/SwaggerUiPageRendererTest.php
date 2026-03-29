<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Advanced\Swagger;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger\SwaggerUiPageRenderer;

final class SwaggerUiPageRendererTest extends TestCase
{
    public function testRendererBuildsHtmlPagePointingToConfiguredSpec(): void
    {
        $renderer = new SwaggerUiPageRenderer(
            new AdvancedPluginConfig(
                openApiTitle: 'PIX Withdrawal API',
                openApiVersion: '1.0.0',
                specPath: '/openapi.json',
                swaggerUiPath: '/docs',
                swaggerUiEnabled: true,
            )
        );

        $page = $renderer->render();

        self::assertStringContainsString('<!DOCTYPE html>', $page);
        self::assertStringContainsString('PIX Withdrawal API - Swagger UI', $page);
        self::assertStringContainsString("url: '/openapi.json'", $page);
        self::assertStringContainsString('swagger-ui-dist@5/swagger-ui-bundle.js', $page);
    }
}
