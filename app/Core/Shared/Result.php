<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

use LogicException;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\CoreException;

final class Result
{
    private function __construct(
        private readonly bool $successful,
        private readonly mixed $value = null,
        private readonly ?CoreException $error = null,
    ) {
    }

    public static function success(mixed $value = null): self
    {
        return new self(true, $value);
    }

    public static function failure(CoreException $error): self
    {
        return new self(false, null, $error);
    }

    public function isSuccess(): bool
    {
        return $this->successful;
    }

    public function isFailure(): bool
    {
        return ! $this->isSuccess();
    }

    public function value(): mixed
    {
        if ($this->isFailure()) {
            throw new LogicException('Cannot access the value of a failed result.');
        }

        return $this->value;
    }

    public function valueOr(mixed $fallback): mixed
    {
        if ($this->isFailure()) {
            return $fallback;
        }

        return $this->value;
    }

    public function error(): CoreException
    {
        if ($this->isSuccess()) {
            throw new LogicException('Cannot access the error of a successful result.');
        }

        return $this->error;
    }
}
