<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Structure;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\BootstrapSanityCheckCommand;

final class CliNamingConventionTest extends TestCase
{
    public function testArchitectureDocumentDescribesCliCommandNamingConventions(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/naming-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`BootstrapSanityCheckCommand`', $documentContents);
        self::assertStringContainsString('`app:sanity-check`', $documentContents);
        self::assertStringContainsString('`app:config:validate`', $documentContents);
        self::assertStringContainsString('`system:check:mysql`', $documentContents);
        self::assertStringContainsString('`system:check:kafka`', $documentContents);
        self::assertStringContainsString('`system:check:mail`', $documentContents);
        self::assertStringContainsString('`system:check:all`', $documentContents);
        self::assertStringContainsString('`withdraw:scheduler:run`', $documentContents);
        self::assertStringContainsString('`withdraw:worker:process`', $documentContents);
        self::assertStringContainsString('evitar assinaturas vagas como `run`, `worker`, `process` ou `check` sem contexto do domínio', $documentContents);
    }

    public function testCurrentCliCommandClassKeepsDocumentedNamingPattern(): void
    {
        $reflection = new ReflectionClass(BootstrapSanityCheckCommand::class);

        self::assertStringEndsWith('Command', $reflection->getShortName());
        self::assertSame('Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Command', $reflection->getNamespaceName());
    }
}
