<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Structure;

use PHPUnit\Framework\TestCase;

final class HealthReadinessContractTest extends TestCase
{
    public function testApiDocumentDescribesOperationalHealthAndReadinessPayloads(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/api/health-readiness-contract.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('GET /health', $documentContents);
        self::assertStringContainsString('GET /ready', $documentContents);
        self::assertStringContainsString('"check": "health"', $documentContents);
        self::assertStringContainsString('"check": "readiness"', $documentContents);
        self::assertStringContainsString('"status": "not_ready"', $documentContents);
        self::assertStringContainsString('"runtime": "http"', $documentContents);
    }
}
