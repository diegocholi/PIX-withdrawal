<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Structure;

use PHPUnit\Framework\TestCase;

final class DataNamingConventionTest extends TestCase
{
    public function testArchitectureDocumentDescribesDataNamingConventions(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/naming-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('## Data Plugin', $documentContents);
        self::assertStringContainsString('`MySqlAccountRepository`', $documentContents);
        self::assertStringContainsString('`AccountWithdrawRecordMapper`', $documentContents);
        self::assertStringContainsString('`DueScheduledWithdrawQuery`', $documentContents);
        self::assertStringContainsString('`CreateAccountTableMigration`', $documentContents);
        self::assertStringContainsString('`DataSchemaMigrationPlan`', $documentContents);
    }
}
