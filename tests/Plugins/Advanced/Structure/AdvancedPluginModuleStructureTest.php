<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Advanced\Structure;

use PHPUnit\Framework\TestCase;

final class AdvancedPluginModuleStructureTest extends TestCase
{
    public function testAdvancedPluginContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesAdvancedPluginNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/advanced-plugin-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Plugins\\\\Advanced\\\\Bootstrap', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Plugins\\\\Advanced\\\\Swagger', $documentContents);
        self::assertStringContainsString('HttpRouteRegistrar', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Plugins/Advanced',
            $projectBasePath . '/app/Plugins/Advanced/Bootstrap',
            $projectBasePath . '/app/Plugins/Advanced/Config',
            $projectBasePath . '/app/Plugins/Advanced/SecurityHeaders',
            $projectBasePath . '/app/Plugins/Advanced/Swagger',
        ];
    }
}
