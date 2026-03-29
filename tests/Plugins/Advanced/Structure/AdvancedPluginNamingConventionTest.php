<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Advanced\Structure;

use PHPUnit\Framework\TestCase;

final class AdvancedPluginNamingConventionTest extends TestCase
{
    public function testArchitectureDocumentDescribesAdvancedPluginNamingConventions(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/naming-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`AdvancedPluginBindingRegistry`', $documentContents);
        self::assertStringContainsString('`AdvancedPluginConfig`', $documentContents);
        self::assertStringContainsString('`OpenApiDocumentFactory`', $documentContents);
        self::assertStringContainsString('`SecurityHeadersMiddleware`', $documentContents);
        self::assertStringContainsString('`SwaggerUiPageRenderer`', $documentContents);
    }

    public function testAdvancedPluginFilesFollowDocumentedSuffixes(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Bootstrap/AdvancedPluginBindingRegistry.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Config/AdvancedPluginConfig.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Config/AdvancedPluginConfigProvider.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/SecurityHeaders/SecurityHeadersPolicy.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/SecurityHeaders/SecurityHeadersMiddleware.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Swagger/OpenApiDocument.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Swagger/OpenApiDocumentFactory.php');
        self::assertFileExists($projectBasePath . '/app/Plugins/Advanced/Swagger/SwaggerUiPageRenderer.php');
    }
}
