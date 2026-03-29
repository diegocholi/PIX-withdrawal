<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Structure;

use PHPUnit\Framework\TestCase;

final class ProviderModuleStructureTest extends TestCase
{
    public function testProvidersModuleContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 3);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesProvidersNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 3);
        $documentPath = $projectBasePath . '/docs/architecture/providers-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Providers\\\\Config', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Providers\\\\Kafka', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Providers\\\\Serialization', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Providers',
            $projectBasePath . '/app/Providers/Bootstrap',
            $projectBasePath . '/app/Providers/Config',
            $projectBasePath . '/app/Providers/Factories',
            $projectBasePath . '/app/Providers/Kafka',
            $projectBasePath . '/app/Providers/Logging',
            $projectBasePath . '/app/Providers/Mail',
            $projectBasePath . '/app/Providers/Metrics',
            $projectBasePath . '/app/Providers/Runtime',
            $projectBasePath . '/app/Providers/Serialization',
            $projectBasePath . '/app/Providers/Support',
        ];
    }
}
