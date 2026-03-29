<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Command;

use Tecnofit\PixWithdrawal\Core\Application\Dto\ValidatedCreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Exception\InvalidCreateWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\Money;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\ScheduleAt;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;

final class CreateWithdrawCommandValidator
{
    public function validate(CreateWithdrawCommand $command, Clock $clock): ValidatedCreateWithdrawData
    {
        $accountId = $this->required($command->accountId(), 'account_id');
        $correlationId = $this->required($command->correlationId(), 'correlation_id');
        $method = $this->parseMethod($command->method());
        $pixKeyType = $this->parsePixKeyType($command->pixKeyType());
        $pixKey = PixKey::from($pixKeyType, $this->required($command->pixKey(), 'pix_key'));
        $amount = Money::fromDecimal($this->required($command->amount(), 'amount'));
        $scheduleAt = $this->parseScheduleAt($command->scheduleAt(), $clock);

        return new ValidatedCreateWithdrawData(
            accountId: $accountId,
            correlationId: $correlationId,
            method: $method,
            pixKey: $pixKey,
            amount: $amount,
            scheduleAt: $scheduleAt,
            traceMetadata: $command->traceMetadata(),
        );
    }

    private function required(string $value, string $field): string
    {
        $normalizedValue = trim($value);

        if ($normalizedValue === '') {
            throw InvalidCreateWithdrawCommand::emptyField($field);
        }

        return $normalizedValue;
    }

    private function parseMethod(string $value): WithdrawMethod
    {
        $method = WithdrawMethod::tryFrom(strtoupper($this->required($value, 'method')));

        if ($method === null) {
            throw InvalidCreateWithdrawCommand::invalidMethod($value);
        }

        return $method;
    }

    private function parsePixKeyType(string $value): PixKeyType
    {
        $pixKeyType = PixKeyType::tryFrom(strtoupper($this->required($value, 'pix_key_type')));

        if ($pixKeyType === null) {
            throw InvalidCreateWithdrawCommand::invalidPixKeyType($value);
        }

        return $pixKeyType;
    }

    private function parseScheduleAt(?string $value, Clock $clock): ?ScheduleAt
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return ScheduleAt::fromString($value, $clock);
    }
}
