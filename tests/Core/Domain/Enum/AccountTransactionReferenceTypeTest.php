<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Domain\Enum;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\AccountTransactionReferenceType;

final class AccountTransactionReferenceTypeTest extends TestCase
{
    public function testReferenceTypeStartsWithWithdraw(): void
    {
        self::assertSame(['WITHDRAW'], AccountTransactionReferenceType::values());
        self::assertTrue(AccountTransactionReferenceType::WITHDRAW->isWithdraw());
    }
}
