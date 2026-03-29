<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Structure;

use PHPUnit\Framework\TestCase;

final class CoreNamespaceConventionTest extends TestCase
{
    public function testCoreContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 3);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesCoreNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 3);
        $documentPath = $projectBasePath . '/docs/architecture/core-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Core\\\\Application\\\\UseCase', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Core\\\\Domain\\\\ValueObject', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Core\\\\Infrastructure\\\\Repository', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Core/Application/Command',
            $projectBasePath . '/app/Core/Application/Dto',
            $projectBasePath . '/app/Core/Application/Exception',
            $projectBasePath . '/app/Core/Application/Query',
            $projectBasePath . '/app/Core/Application/UseCase',
            $projectBasePath . '/app/Core/Domain/Contract',
            $projectBasePath . '/app/Core/Domain/Entity',
            $projectBasePath . '/app/Core/Domain/Enum',
            $projectBasePath . '/app/Core/Domain/Event',
            $projectBasePath . '/app/Core/Domain/Exception',
            $projectBasePath . '/app/Core/Domain/Service',
            $projectBasePath . '/app/Core/Domain/ValueObject',
            $projectBasePath . '/app/Core/Infrastructure/Contract',
            $projectBasePath . '/app/Core/Infrastructure/Repository',
            $projectBasePath . '/app/Core/Shared/Contract',
            $projectBasePath . '/app/Core/Shared/Exception',
        ];
    }
}
