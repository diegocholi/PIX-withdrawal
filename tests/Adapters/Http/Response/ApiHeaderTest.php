<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Response;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;

final class ApiHeaderTest extends TestCase
{
    public function testApiHeaderConstantsFollowDocumentedConvention(): void
    {
        self::assertSame('Accept', ApiHeader::ACCEPT);
        self::assertSame('Content-Type', ApiHeader::CONTENT_TYPE);
        self::assertSame('X-Correlation-Id', ApiHeader::CORRELATION_ID);
        self::assertSame('Location', ApiHeader::LOCATION);
    }

    public function testApiDocumentDescribesHeaderConvention(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/api/http-header-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`Accept: application/json`', $documentContents);
        self::assertStringContainsString('`Content-Type: application/json`', $documentContents);
        self::assertStringContainsString('`X-Correlation-Id`', $documentContents);
        self::assertStringContainsString('`Location`', $documentContents);
    }
}
