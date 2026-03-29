<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Request;

use DateTimeImmutable;
use InvalidArgumentException;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawPixRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawRequestPayload;
use Tecnofit\PixWithdrawal\Adapters\Http\Dto\Public\CreateWithdrawScheduleRequestPayload;

final class CreateWithdrawRequest
{
    /**
     * @param mixed $payload
     */
    public function validate(mixed $payload): CreateWithdrawRequestPayload
    {
        if (! is_array($payload)) {
            throw new InvalidArgumentException('The withdraw request payload must be an object.');
        }

        $pixPayload = $payload['pix'] ?? null;

        if (! is_array($pixPayload)) {
            throw new InvalidArgumentException('The withdraw request payload must contain a pix object.');
        }

        return new CreateWithdrawRequestPayload(
            amount: $this->validateAmount($payload),
            method: $this->validateMethod($payload),
            pix: new CreateWithdrawPixRequestPayload(
                keyType: $this->validatePixKeyType($pixPayload),
                key: $this->requireString($pixPayload, 'key'),
            ),
            schedule: $this->buildSchedulePayload($payload['schedule'] ?? null),
        );
    }

    /**
     * @param mixed $payload
     */
    private function buildSchedulePayload(mixed $payload): ?CreateWithdrawScheduleRequestPayload
    {
        if ($payload === null) {
            return null;
        }

        if (! is_array($payload)) {
            throw new InvalidArgumentException('The schedule field must be an object or null.');
        }

        return new CreateWithdrawScheduleRequestPayload(
            $this->validateScheduleAt($payload),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function requireString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;

        if (! is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException(sprintf('The "%s" field must be a non-empty string.', $field));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateMethod(array $payload): string
    {
        $method = strtoupper(trim($this->requireString($payload, 'method')));

        if ($method !== 'PIX') {
            throw new InvalidArgumentException(sprintf('The "method" field must be "PIX", "%s" given.', $method));
        }

        return $method;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validatePixKeyType(array $payload): string
    {
        $pixKeyType = strtoupper(trim($this->requireString($payload, 'key_type')));

        if ($pixKeyType !== 'EMAIL') {
            throw new InvalidArgumentException(sprintf('The "pix.key_type" field must be "EMAIL", "%s" given.', $pixKeyType));
        }

        return $pixKeyType;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateScheduleAt(array $payload): string
    {
        $scheduleAt = trim($this->requireString($payload, 'at'));
        $date = DateTimeImmutable::createFromFormat(DATE_ATOM, $scheduleAt);

        if ($date === false || $date->format(DATE_ATOM) !== $scheduleAt) {
            throw new InvalidArgumentException(sprintf('The "schedule.at" field must use RFC 3339 format, "%s" given.', $scheduleAt));
        }

        return $scheduleAt;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function validateAmount(array $payload): string
    {
        $amount = trim($this->requireString($payload, 'amount'));

        if (! preg_match('/^\d+\.\d{2}$/', $amount)) {
            throw new InvalidArgumentException(sprintf('The "amount" field must be a positive decimal with two fraction digits, "%s" given.', $amount));
        }

        if ((int) str_replace('.', '', $amount) <= 0) {
            throw new InvalidArgumentException(sprintf('The "amount" field must be greater than zero, "%s" given.', $amount));
        }

        return $amount;
    }
}
