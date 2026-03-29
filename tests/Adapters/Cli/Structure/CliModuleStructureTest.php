<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Structure;

use PHPUnit\Framework\TestCase;

final class CliModuleStructureTest extends TestCase
{
    public function testCliAdapterContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesCliNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/cli-adapter-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Cli\\\\Bootstrap', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Cli\\\\Command', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Cli\\\\Worker', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Cli\\\\Diagnostic', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Adapters/Cli',
            $projectBasePath . '/app/Adapters/Cli/Bootstrap',
            $projectBasePath . '/app/Adapters/Cli/Command',
            $projectBasePath . '/app/Adapters/Cli/Diagnostic',
            $projectBasePath . '/app/Adapters/Cli/Scheduler',
            $projectBasePath . '/app/Adapters/Cli/Support',
            $projectBasePath . '/app/Adapters/Cli/Worker',
        ];
    }
}
