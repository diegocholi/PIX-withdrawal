<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Structure;

use PHPUnit\Framework\TestCase;

final class CliCommandContractTest extends TestCase
{
    public function testArchitectureDocumentDescribesCliArgumentsFlagsAndExitCodes(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/cli-command-contract.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`app:seed:case`', $documentContents);
        self::assertStringContainsString('`app:schema:migrate`', $documentContents);
        self::assertStringContainsString('`app:config:validate`', $documentContents);
        self::assertStringContainsString('`system:check:mysql`', $documentContents);
        self::assertStringContainsString('`system:check:kafka`', $documentContents);
        self::assertStringContainsString('`system:check:mail`', $documentContents);
        self::assertStringContainsString('`system:check:all`', $documentContents);
        self::assertStringContainsString('`--correlation-id`', $documentContents);
        self::assertStringContainsString('`--dry-run`', $documentContents);
        self::assertStringContainsString('`--batch-size`', $documentContents);
        self::assertStringContainsString('`--max-messages`', $documentContents);
        self::assertStringContainsString('`--group-id`', $documentContents);
        self::assertStringContainsString('`--topic`', $documentContents);
        self::assertStringContainsString('`--poll-timeout`', $documentContents);
        self::assertStringContainsString('`CliOptionName`', $documentContents);
        self::assertStringContainsString('`CliExitCode`', $documentContents);
        self::assertStringContainsString('`130`', $documentContents);
    }
}
