<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;

final class InvalidPixKeyTest extends TestCase
{
    public function testUnsupportedTypeCarriesStableMetadata(): void
    {
        $exception = InvalidPixKey::unsupportedType(PixKeyType::PHONE);

        self::assertSame('pix_key.unsupported_type', $exception->errorCode());
        self::assertSame(['type' => 'PHONE'], $exception->context());
    }
}
