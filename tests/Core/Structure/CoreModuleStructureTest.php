<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Structure;

use PHPUnit\Framework\TestCase;

final class CoreModuleStructureTest extends TestCase
{
    public function testCoreModuleContainsExpectedBaseDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 3);

        foreach ($this->expectedDirectories($projectBasePath) as $directory) {
            self::assertDirectoryExists($directory);
        }
    }

    /**
     * @return list<string>
     */
    private function expectedDirectories(string $projectBasePath): array
    {
        return [
            $projectBasePath . '/app/Core/Application',
            $projectBasePath . '/app/Core/Domain',
            $projectBasePath . '/app/Core/Infrastructure',
            $projectBasePath . '/app/Core/Shared',
        ];
    }
}
