<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Support;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;

final class CliExitCodeTest extends TestCase
{
    public function testCliExitCodeCatalogMatchesDocumentedValues(): void
    {
        self::assertSame([0, 2, 3, 4, 5, 130], CliExitCode::all());
        self::assertSame(0, CliExitCode::SUCCESS);
        self::assertSame(2, CliExitCode::INVALID_ARGUMENT);
        self::assertSame(3, CliExitCode::DEPENDENCY_FAILURE);
        self::assertSame(4, CliExitCode::PARTIAL_FAILURE);
        self::assertSame(5, CliExitCode::RUNTIME_FAILURE);
        self::assertSame(130, CliExitCode::INTERRUPTED);
    }
}
