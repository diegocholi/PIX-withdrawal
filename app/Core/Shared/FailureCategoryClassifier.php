<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

use Throwable;
use Tecnofit\PixWithdrawal\Core\Application\Exception\AccountNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\DuplicateWithdrawRequestBlocked;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InsufficientWithdrawBalance;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotFound;
use Tecnofit\PixWithdrawal\Core\Application\Exception\WithdrawNotProcessable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\FailureCategory;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InsufficientFundsException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidPixKeyException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidWithdrawStateException;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\UnsupportedWithdrawMethodException;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;

final class FailureCategoryClassifier implements FailureClassifier
{
    public function classify(Throwable $throwable): FailureCategory
    {
        return match (true) {
            $throwable instanceof InvalidCreateWithdrawCommand,
            $throwable instanceof InvalidPixKeyException,
            $throwable instanceof InvalidScheduleException,
            $throwable instanceof InvalidWithdrawStateException,
            $throwable instanceof UnsupportedWithdrawMethodException => FailureCategory::VALIDATION,

            $throwable instanceof InsufficientFundsException,
            $throwable instanceof InsufficientWithdrawBalance,
            $throwable instanceof AccountNotFound,
            $throwable instanceof DuplicateWithdrawRequestBlocked,
            $throwable instanceof WithdrawNotFound,
            $throwable instanceof WithdrawNotProcessable => FailureCategory::BUSINESS,

            $throwable instanceof TransientInfrastructureException => FailureCategory::INFRASTRUCTURE_TRANSIENT,

            default => FailureCategory::INTERNAL,
        };
    }
}
