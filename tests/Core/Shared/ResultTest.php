<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use LogicException;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\CoreException;
use Tecnofit\PixWithdrawal\Core\Shared\Result;

final class ResultTest extends TestCase
{
    public function testSuccessResultExposesValue(): void
    {
        $result = Result::success(['withdraw_id' => 'w-1']);

        self::assertTrue($result->isSuccess());
        self::assertFalse($result->isFailure());
        self::assertSame(['withdraw_id' => 'w-1'], $result->value());
        self::assertSame(['withdraw_id' => 'w-1'], $result->valueOr(['fallback' => true]));
    }

    public function testFailureResultExposesError(): void
    {
        $error = new CoreException(
            message: 'Insufficient funds.',
            errorCode: 'withdraw.insufficient_funds',
            context: ['account_id' => 'acc-1']
        );

        $result = Result::failure($error);

        self::assertTrue($result->isFailure());
        self::assertFalse($result->isSuccess());
        self::assertSame($error, $result->error());
        self::assertSame('withdraw.insufficient_funds', $result->error()->errorCode());
        self::assertSame(['account_id' => 'acc-1'], $result->error()->context());
        self::assertSame('fallback', $result->valueOr('fallback'));
    }

    public function testSuccessResultCannotExposeError(): void
    {
        $result = Result::success();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot access the error of a successful result.');

        $result->error();
    }

    public function testFailureResultCannotExposeValue(): void
    {
        $result = Result::failure(new CoreException('Validation failed.'));

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Cannot access the value of a failed result.');

        $result->value();
    }
}
