<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Structure;

use PHPUnit\Framework\TestCase;

final class HttpModuleStructureTest extends TestCase
{
    public function testHttpModuleContainsExpectedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
            self::assertFileExists($directory . '/README.md');
        }
    }

    public function testArchitectureDocumentDescribesHttpNamespaceMap(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/http-adapter-namespace-map.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Http\\\\Controller', $documentContents);
        self::assertStringContainsString('Tecnofit\\\\PixWithdrawal\\\\Adapters\\\\Http\\\\Request', $documentContents);
        self::assertStringContainsString('config/routes.php', $documentContents);
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Adapters/Http',
            $projectBasePath . '/app/Adapters/Http/Controller',
            $projectBasePath . '/app/Adapters/Http/Dto',
            $projectBasePath . '/app/Adapters/Http/Dto/Public',
            $projectBasePath . '/app/Adapters/Http/Request',
            $projectBasePath . '/app/Adapters/Http/Response',
            $projectBasePath . '/app/Adapters/Http/Mapper',
            $projectBasePath . '/app/Adapters/Http/Middleware',
            $projectBasePath . '/app/Adapters/Http/Exception',
            $projectBasePath . '/app/Adapters/Http/Exception/Handler',
            $projectBasePath . '/app/Adapters/Http/Health',
            $projectBasePath . '/app/Adapters/Http/Route',
        ];
    }
}
