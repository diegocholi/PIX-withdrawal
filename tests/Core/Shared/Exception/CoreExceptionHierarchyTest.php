<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared\Exception;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\ApplicationException;
use Tecnofit\PixWithdrawal\Core\Application\Exception\CoreProcessingException;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\DomainException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientFundsException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKeyException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStateException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStatusTransition;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\UnsupportedWithdrawMethodException;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\CoreException;

final class CoreExceptionHierarchyTest extends TestCase
{
    public function testApplicationExceptionsExtendCoreException(): void
    {
        self::assertInstanceOf(CoreException::class, new ApplicationException('application error'));
        self::assertInstanceOf(ApplicationException::class, new CoreProcessingException('processing error'));
        self::assertInstanceOf(CoreProcessingException::class, WithdrawNotProcessable::withStatus('wd-1', WithdrawStatus::PENDING));
    }

    public function testDomainExceptionsExtendSemanticBases(): void
    {
        self::assertInstanceOf(CoreException::class, new DomainException('domain error'));
        self::assertInstanceOf(DomainException::class, UnsupportedWithdrawMethodException::withMethod('TED'));
        self::assertInstanceOf(InsufficientFundsException::class, InsufficientAccountBalance::forDebit(
            'acc-1',
            Money::fromDecimal('10.00'),
            Money::fromDecimal('20.00'),
        ));
        self::assertInstanceOf(InvalidWithdrawStateException::class, InvalidWithdrawStatusTransition::fromTo(
            WithdrawStatus::PENDING,
            WithdrawStatus::DONE,
        ));
        self::assertInstanceOf(InvalidPixKeyException::class, InvalidPixKey::invalidEmail('invalid'));
        self::assertInstanceOf(InvalidScheduleException::class, InvalidScheduleAt::invalidFormat('invalid'));
    }
}
