<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Entity;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\Exception\InvalidAccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;

final class AccountWithdrawPix
{
    private function __construct(
        private string $withdrawId,
        private WithdrawMethod $method,
        private PixKey $pixKey,
        private DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->withdrawId = trim($this->withdrawId);

        $this->guardState();
    }

    public static function create(
        string $withdrawId,
        WithdrawMethod $method,
        PixKey $pixKey,
        DateTimeImmutable $createdAt,
    ): self {
        return new self(
            withdrawId: $withdrawId,
            method: $method,
            pixKey: $pixKey,
            createdAt: $createdAt,
            updatedAt: $createdAt,
        );
    }

    public static function reconstitute(
        string $withdrawId,
        WithdrawMethod $method,
        PixKey $pixKey,
        DateTimeImmutable $createdAt,
        DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            withdrawId: $withdrawId,
            method: $method,
            pixKey: $pixKey,
            createdAt: $createdAt,
            updatedAt: $updatedAt,
        );
    }

    public function withdrawId(): string
    {
        return $this->withdrawId;
    }

    public function method(): WithdrawMethod
    {
        return $this->method;
    }

    public function pixKey(): PixKey
    {
        return $this->pixKey;
    }

    public function pixKeyType(): \Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType
    {
        return $this->pixKey->type();
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    private function guardState(): void
    {
        if ($this->withdrawId === '') {
            throw InvalidAccountWithdrawPix::emptyWithdrawId();
        }

        if (! $this->method->supportsPixPayload()) {
            throw InvalidAccountWithdrawPix::unsupportedMethod($this->method);
        }

        if (! $this->method->supportsPixKeyType($this->pixKeyType())) {
            throw InvalidAccountWithdrawPix::unsupportedPixKeyType($this->method, $this->pixKeyType());
        }

        if ($this->updatedAt < $this->createdAt) {
            throw InvalidAccountWithdrawPix::inconsistentTimestamps($this->createdAt, $this->updatedAt);
        }
    }
}
