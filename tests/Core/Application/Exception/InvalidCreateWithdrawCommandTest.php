<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;

final class InvalidCreateWithdrawCommandTest extends TestCase
{
    public function testEmptyFieldFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidCreateWithdrawCommand::emptyField('method');

        self::assertSame('create_withdraw_command.empty_field', $exception->errorCode());
        self::assertSame(['field' => 'method'], $exception->context());
    }

    public function testInvalidMethodFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidCreateWithdrawCommand::invalidMethod('ted');

        self::assertSame('create_withdraw_command.invalid_method', $exception->errorCode());
        self::assertSame(['method' => 'ted'], $exception->context());
    }

    public function testInvalidPixKeyTypeFactoryCarriesStableMetadata(): void
    {
        $exception = InvalidCreateWithdrawCommand::invalidPixKeyType('document');

        self::assertSame('create_withdraw_command.invalid_pix_key_type', $exception->errorCode());
        self::assertSame(['pix_key_type' => 'document'], $exception->context());
    }
}
