<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Shared;

use PHPUnit\Framework\TestCase;
use RuntimeException;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientAccountBalance;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKey;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\FailureCategoryClassifier;

final class FailureCategoryClassifierTest extends TestCase
{
    public function testClassifierMapsValidationFailures(): void
    {
        $classifier = new FailureCategoryClassifier();

        self::assertSame(FailureCategory::VALIDATION, $classifier->classify(InvalidCreateWithdrawCommand::emptyField('amount')));
        self::assertSame(FailureCategory::VALIDATION, $classifier->classify(InvalidPixKey::invalidEmail('invalid')));
        self::assertSame(FailureCategory::VALIDATION, $classifier->classify(InvalidScheduleAt::invalidFormat('invalid')));
    }

    public function testClassifierMapsBusinessFailures(): void
    {
        $classifier = new FailureCategoryClassifier();

        self::assertSame(FailureCategory::BUSINESS, $classifier->classify(AccountNotFound::withId('acc-1')));
        self::assertSame(
            FailureCategory::BUSINESS,
            $classifier->classify(
                InsufficientAccountBalance::forDebit(
                    'acc-1',
                    \Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money::fromDecimal('10.00'),
                    \Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money::fromDecimal('20.00'),
                )
            )
        );
        self::assertSame(
            FailureCategory::BUSINESS,
            $classifier->classify(
                InsufficientWithdrawBalance::forImmediateWithdraw(
                    \Tecnofit\PixWithdrawal\Core\Domain\Entity\Account::create(
                        'acc-1',
                        'Main Account',
                        \Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money::fromDecimal('10.00'),
                        new \DateTimeImmutable('2026-03-28T09:00:00+00:00'),
                    ),
                    \Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money::fromDecimal('20.00'),
                )
            )
        );
    }

    public function testClassifierMapsTransientAndInternalFailures(): void
    {
        $classifier = new FailureCategoryClassifier();

        self::assertSame(
            FailureCategory::INFRASTRUCTURE_TRANSIENT,
            $classifier->classify(new TransientInfrastructureException('temporary broker failure'))
        );
        self::assertSame(FailureCategory::INTERNAL, $classifier->classify(new RuntimeException('unexpected')));
    }
}
