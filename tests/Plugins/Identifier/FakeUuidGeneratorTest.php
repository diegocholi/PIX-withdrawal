<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Identifier;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Plugins\Identifier\FakeUuidGenerator;

final class FakeUuidGeneratorTest extends TestCase
{
    public function testGenerateReturnsPredictableSequenceForTests(): void
    {
        $generator = new FakeUuidGenerator();

        self::assertInstanceOf(UuidGenerator::class, $generator);
        self::assertSame('00000000-0000-4000-8000-000000000001', $generator->generate());
        self::assertSame('00000000-0000-4000-8000-000000000002', $generator->generate());
        self::assertSame('00000000-0000-4000-8000-000000000003', $generator->generate());
    }
}
