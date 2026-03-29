<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Structure;

use PHPUnit\Framework\TestCase;

final class HttpNamingConventionTest extends TestCase
{
    public function testArchitectureDocumentDescribesHttpNamingConventions(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $documentPath = $projectBasePath . '/docs/architecture/naming-conventions.md';

        self::assertFileExists($documentPath);

        $documentContents = (string) file_get_contents($documentPath);

        self::assertStringContainsString('`WithdrawController`', $documentContents);
        self::assertStringContainsString('`CreateWithdrawRequest`', $documentContents);
        self::assertStringContainsString('`WithdrawAcceptedResponse`', $documentContents);
        self::assertStringContainsString('`Dto/Public`', $documentContents);
    }

    public function testCurrentHttpClassesFollowDocumentedSuffixes(): void
    {
        $projectBasePath = dirname(__DIR__, 4);

        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Controller/BootstrapController.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Controller/HealthController.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Controller/WithdrawController.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Controller/WithdrawStatusController.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Request/CreateWithdrawRequest.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Response/WithdrawAcceptedResponse.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Mapper/FindWithdrawStatusRequestMapper.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Exception/Handler/UnexpectedThrowableHandler.php');
        self::assertFileExists($projectBasePath . '/app/Adapters/Http/Route/HttpRouteRegistrar.php');
    }
}
