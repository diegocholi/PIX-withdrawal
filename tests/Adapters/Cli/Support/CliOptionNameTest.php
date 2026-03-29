<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Support;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOptionName;

final class CliOptionNameTest extends TestCase
{
    public function testCliOptionCatalogMatchesPlannedOperationalFlags(): void
    {
        self::assertSame(
            ['correlation-id', 'dry-run', 'batch-size', 'max-messages', 'idle-timeout', 'group-id', 'topic', 'poll-timeout', 'force'],
            CliOptionName::all(),
        );
    }
}
