<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Structure;

use PHPUnit\Framework\TestCase;

final class ProviderNamingConventionTest extends TestCase
{
    public function testArchitectureDocumentDescribesProviderNamingConventions(): void
    {
        $projectBasePath = dirname(__DIR__, 3);
        $documentPath = $projectBasePath . '/docs/architecture/naming-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`ProviderRuntimeBootstrap`', $documentContents);
        self::assertStringContainsString('`KafkaProducerFactory`', $documentContents);
        self::assertStringContainsString('`SmtpWithdrawMailer`', $documentContents);
        self::assertStringContainsString('`HyperfStructuredLogger`', $documentContents);
        self::assertStringContainsString('nao criar logger exclusivo por provider', $documentContents);
    }

    public function testProviderModuleContainsDocumentedNamespaceDirectories(): void
    {
        $projectBasePath = dirname(__DIR__, 3);

        self::assertDirectoryExists($projectBasePath . '/app/Providers/Bootstrap');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Config');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Factories');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Kafka');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Mail');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Logging');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Metrics');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Runtime');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Serialization');
        self::assertDirectoryExists($projectBasePath . '/app/Providers/Support');
    }
}
