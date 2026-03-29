<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccount;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountTransaction;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidMoney;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStatusTransition;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\UnsupportedWithdrawMethodException;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\CoreException;

final class ErrorCodeTest extends TestCase
{
    public function testCatalogContainsUniqueStableCodes(): void
    {
        $codes = ErrorCode::all();

        self::assertNotEmpty($codes);
        self::assertSame($codes, array_values($codes));
        self::assertSame($codes, array_values(array_unique($codes)));
        self::assertContains(ErrorCode::CORE_UNEXPECTED_ERROR, $codes);
        self::assertContains(ErrorCode::WITHDRAW_DUPLICATE_REQUEST_BLOCKED, $codes);
        self::assertContains(ErrorCode::WITHDRAW_INSUFFICIENT_FUNDS, $codes);
        self::assertContains(ErrorCode::WITHDRAW_NOT_PROCESSABLE, $codes);
        self::assertContains(ErrorCode::ACCOUNT_WITHDRAW_INVALID_STATUS_TRANSITION, $codes);
    }

    public function testCoreExceptionDefaultsToCatalogUnexpectedError(): void
    {
        $exception = new CoreException('unexpected');

        self::assertSame(ErrorCode::CORE_UNEXPECTED_ERROR, $exception->errorCode());
    }

    public function testConcreteExceptionsUseCatalogCodes(): void
    {
        $expectations = [
            [AccountNotFound::withId('acc-1'), ErrorCode::ACCOUNT_NOT_FOUND],
            [DuplicateWithdrawRequestBlocked::becauseRecentEquivalentRequestExists(
                AccountWithdraw::createQueued(
                    id: 'wd-dup-1',
                    accountId: 'acc-1',
                    method: WithdrawMethod::PIX,
                    amount: Money::fromDecimal('10.00'),
                    correlationId: 'corr-dup-1',
                    idempotencyKey: 'idem-dup-1',
                    queuedAt: new \DateTimeImmutable('2026-03-28T10:00:00+00:00'),
                    duplicateGuardFingerprint: 'guard-dup-1',
                ),
                45,
            ), ErrorCode::WITHDRAW_DUPLICATE_REQUEST_BLOCKED],
            [InsufficientWithdrawBalance::forImmediateWithdraw(
                \Tecnofit\PixWithdrawal\Core\Domain\Entity\Account::create(
                    'acc-1',
                    'Main Account',
                    Money::fromDecimal('10.00'),
                    new \DateTimeImmutable('2026-03-28T10:00:00+00:00'),
                ),
                Money::fromDecimal('20.00'),
            ), ErrorCode::WITHDRAW_INSUFFICIENT_FUNDS],
            [InvalidCreateWithdrawCommand::emptyField('method'), ErrorCode::CREATE_WITHDRAW_COMMAND_EMPTY_FIELD],
            [InvalidCreateWithdrawCommand::invalidMethod('ted'), ErrorCode::CREATE_WITHDRAW_COMMAND_INVALID_METHOD],
            [InvalidCreateWithdrawCommand::invalidPixKeyType('document'), ErrorCode::CREATE_WITHDRAW_COMMAND_INVALID_PIX_KEY_TYPE],
            [WithdrawNotFound::withId('wd-1'), ErrorCode::WITHDRAW_NOT_FOUND],
            [WithdrawNotProcessable::withStatus('wd-1', WithdrawStatus::PENDING), ErrorCode::WITHDRAW_NOT_PROCESSABLE],
            [InsufficientAccountBalance::forDebit('acc-1', Money::fromDecimal('10.00'), Money::fromDecimal('20.00')), ErrorCode::ACCOUNT_INSUFFICIENT_BALANCE],
            [InvalidAccount::emptyId(), ErrorCode::ACCOUNT_EMPTY_ID],
            [InvalidAccount::emptyName(), ErrorCode::ACCOUNT_EMPTY_NAME],
            [InvalidAccountTransaction::emptyField('reference_id'), ErrorCode::ACCOUNT_TRANSACTION_EMPTY_FIELD],
            [InvalidAccountTransaction::zeroAmount(), ErrorCode::ACCOUNT_TRANSACTION_ZERO_AMOUNT],
            [InvalidAccountWithdraw::emptyField('account_id'), ErrorCode::ACCOUNT_WITHDRAW_EMPTY_FIELD],
            [InvalidAccountWithdraw::negativeRetryCount(-1), ErrorCode::ACCOUNT_WITHDRAW_NEGATIVE_RETRY_COUNT],
            [InvalidAccountWithdrawPix::emptyWithdrawId(), ErrorCode::ACCOUNT_WITHDRAW_PIX_EMPTY_WITHDRAW_ID],
            [InvalidMoney::negativeAmount('-1.00'), ErrorCode::MONEY_NEGATIVE_AMOUNT],
            [InvalidMoney::invalidFormat('10,00'), ErrorCode::MONEY_INVALID_FORMAT],
            [InvalidMoney::insufficientAmount('10.00', '20.00'), ErrorCode::MONEY_INSUFFICIENT_AMOUNT],
            [InvalidPixKey::unsupportedType(PixKeyType::PHONE), ErrorCode::PIX_KEY_UNSUPPORTED_TYPE],
            [InvalidPixKey::invalidEmail('invalid'), ErrorCode::PIX_KEY_INVALID_EMAIL],
            [InvalidScheduleAt::invalidFormat('invalid'), ErrorCode::SCHEDULE_AT_INVALID_FORMAT],
            [InvalidScheduleAt::pastDate('2026-01-01T00:00:00+00:00', '2026-01-02T00:00:00+00:00'), ErrorCode::SCHEDULE_AT_PAST_DATE],
            [InvalidWithdrawStatusTransition::fromTo(WithdrawStatus::PENDING, WithdrawStatus::DONE), ErrorCode::ACCOUNT_WITHDRAW_INVALID_STATUS_TRANSITION],
            [UnsupportedWithdrawMethodException::withMethod('TED'), ErrorCode::WITHDRAW_UNSUPPORTED_METHOD],
        ];

        foreach ($expectations as [$exception, $expectedCode]) {
            self::assertSame($expectedCode, $exception->errorCode());
        }
    }
}
