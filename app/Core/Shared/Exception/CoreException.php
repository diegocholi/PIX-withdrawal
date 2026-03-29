<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Exception;

use RuntimeException;
use Throwable;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

class CoreException extends RuntimeException
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        string $message,
        private readonly string $errorCode = ErrorCode::CORE_UNEXPECTED_ERROR,
        private readonly array $context = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }
}
