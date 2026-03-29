<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Structure;

use PHPUnit\Framework\TestCase;

final class HttpSurfaceHardeningPolicyTest extends TestCase
{
    public function testHardeningPolicyDocumentDescribesPayloadLimitAndSecureHeaders(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/api/http-surface-hardening-policy.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('POST /account/{accountId}/balance/withdraw', $documentContents);
        self::assertStringContainsString('16 KiB', $documentContents);
        self::assertStringContainsString('413 Payload Too Large', $documentContents);
        self::assertStringContainsString('415 Unsupported Media Type', $documentContents);
        self::assertStringContainsString('Cache-Control: no-store', $documentContents);
        self::assertStringContainsString('Pragma: no-cache', $documentContents);
        self::assertStringContainsString('X-Content-Type-Options: nosniff', $documentContents);
        self::assertStringContainsString('ADV-8.1-T1', $documentContents);
    }

    public function testApiDocumentsReferenceHardeningPolicy(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $headerConventionPath = $projectBasePath . '/docs/api/http-header-conventions.md';
        $createWithdrawContractPath = $projectBasePath . '/docs/api/create-withdraw-contract.md';
        $findWithdrawStatusContractPath = $projectBasePath . '/docs/api/find-withdraw-status-contract.md';

        self::assertFileExists($headerConventionPath);
        self::assertFileExists($createWithdrawContractPath);
        self::assertFileExists($findWithdrawStatusContractPath);

        self::assertStringContainsString(
            'docs/api/http-surface-hardening-policy.md',
            (string) file_get_contents($headerConventionPath)
        );
        self::assertStringContainsString(
            'docs/api/http-surface-hardening-policy.md',
            (string) file_get_contents($createWithdrawContractPath)
        );
        self::assertStringContainsString(
            'docs/api/http-surface-hardening-policy.md',
            (string) file_get_contents($findWithdrawStatusContractPath)
        );
    }
}
