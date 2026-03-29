<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Structure;

use PHPUnit\Framework\TestCase;

final class CreateWithdrawRateLimitingGuidelineTest extends TestCase
{
    public function testApiDocumentDescribesCreateWithdrawRateLimitingGuideline(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/api/create-withdraw-rate-limiting-guideline.md';
        $createWithdrawContractPath = $projectBasePath . '/docs/api/create-withdraw-contract.md';

        self::assertFileExists($documentPath);
        self::assertFileExists($createWithdrawContractPath);

        $documentContents = (string) file_get_contents($documentPath);
        $contractContents = (string) file_get_contents($createWithdrawContractPath);

        self::assertStringContainsString('POST /account/{accountId}/balance/withdraw', $documentContents);
        self::assertStringContainsString('429 Too Many Requests', $documentContents);
        self::assertStringContainsString('meta.correlation_id', $documentContents);
        self::assertStringContainsString('middleware, guard ou componente equivalente de borda', $documentContents);
        self::assertStringContainsString('ADV-4.1-T1', $documentContents);
        self::assertStringContainsString('ADV-4.1-T2', $documentContents);
        self::assertStringContainsString('## Diretriz de rate limiting', $contractContents);
        self::assertStringContainsString('docs/api/create-withdraw-rate-limiting-guideline.md', $contractContents);
    }
}
