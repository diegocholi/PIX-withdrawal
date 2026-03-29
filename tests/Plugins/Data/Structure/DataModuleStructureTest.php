<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Structure;

use PHPUnit\Framework\TestCase;

final class DataModuleStructureTest extends TestCase
{
    public function testDataPluginContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesDataPluginNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/data-plugin-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Plugins\\\\Data\\\\Bootstrap', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Plugins\\\\Data\\\\Config', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Plugins\\\\Data\\\\Repositories', $documentContents);
        self::assertStringContainsString('Core\\\\Infrastructure\\\\Repository', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Plugins/Data',
            $projectBasePath . '/app/Plugins/Data/Bootstrap',
            $projectBasePath . '/app/Plugins/Data/Config',
            $projectBasePath . '/app/Plugins/Data/Mappers',
            $projectBasePath . '/app/Plugins/Data/Migrations',
            $projectBasePath . '/app/Plugins/Data/Queries',
            $projectBasePath . '/app/Plugins/Data/Repositories',
            $projectBasePath . '/app/Plugins/Data/Seeds',
            $projectBasePath . '/app/Plugins/Data/Transactions',
        ];
    }
}
