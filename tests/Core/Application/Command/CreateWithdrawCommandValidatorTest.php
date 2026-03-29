<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Command;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommandValidator;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidMoney;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class CreateWithdrawCommandValidatorTest extends TestCase
{
    public function testValidatorReturnsTypedDataForValidCommand(): void
    {
        $validated = (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput(
                ' acc-1 ',
                ' corr-1 ',
                'pix',
                'email',
                ' User@Example.Com ',
                '100.50',
                '2026-03-29T10:00:00-03:00',
            ),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );

        self::assertSame(
            [
                'account_id' => 'acc-1',
                'correlation_id' => 'corr-1',
                'method' => 'PIX',
                'pix_key' => [
                    'type' => 'EMAIL',
                    'value' => 'user@example.com',
                ],
                'amount' => [
                    'amount' => '100.50',
                    'minor_amount' => 10050,
                ],
                'schedule_at' => '2026-03-29T10:00:00-03:00',
                'trace_metadata' => [],
            ],
            $validated->toArray()
        );
    }

    public function testValidatorRejectsEmptyRequiredField(): void
    {
        $this->expectException(InvalidCreateWithdrawCommand::class);
        $this->expectExceptionMessage('Create withdraw command account_id cannot be empty.');

        (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput('   ', 'corr-1', 'PIX', 'EMAIL', 'user@example.com', '100.00'),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testValidatorRejectsInvalidMethod(): void
    {
        $this->expectException(InvalidCreateWithdrawCommand::class);
        $this->expectExceptionMessage('Create withdraw command method is invalid.');

        (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput('acc-1', 'corr-1', 'TED', 'EMAIL', 'user@example.com', '100.00'),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testValidatorRejectsInvalidPixKeyType(): void
    {
        $this->expectException(InvalidCreateWithdrawCommand::class);
        $this->expectExceptionMessage('Create withdraw command pix_key_type is invalid.');

        (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput('acc-1', 'corr-1', 'PIX', 'UNKNOWN', 'user@example.com', '100.00'),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testValidatorDelegatesAmountValidationToMoneyValueObject(): void
    {
        $this->expectException(InvalidMoney::class);

        (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput('acc-1', 'corr-1', 'PIX', 'EMAIL', 'user@example.com', 'invalid'),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );
    }

    public function testValidatorDelegatesScheduleValidationToScheduleAtValueObject(): void
    {
        $this->expectException(InvalidScheduleAt::class);

        (new CreateWithdrawCommandValidator())->validate(
            new CreateWithdrawInput(
                'acc-1',
                'corr-1',
                'PIX',
                'EMAIL',
                'user@example.com',
                '100.00',
                '2026-03-27T10:00:00-03:00',
            ),
            $this->clockAt('2026-03-28T10:00:00-03:00'),
        );
    }

    private function clockAt(string $dateTime): Clock
    {
        return new class ($dateTime) implements Clock {
            public function __construct(private string $dateTime)
            {
            }

            public function now(): DateTimeImmutable
            {
                return new DateTimeImmutable($this->dateTime);
            }
        };
    }
}
