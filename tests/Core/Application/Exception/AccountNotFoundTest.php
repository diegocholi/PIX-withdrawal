<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;

final class AccountNotFoundTest extends TestCase
{
    public function testFactoryCarriesStableMetadata(): void
    {
        $exception = AccountNotFound::withId('acc-1');

        self::assertSame('account.not_found', $exception->errorCode());
        self::assertSame(['account_id' => 'acc-1'], $exception->context());
    }
}
