<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Domain\Exception;

use DateTimeImmutable;
use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidAccount extends DomainException
{
    public static function emptyId(): self
    {
        return new self(
            message: 'Account id cannot be empty.',
            errorCode: ErrorCode::ACCOUNT_EMPTY_ID,
            context: []
        );
    }

    public static function emptyName(): self
    {
        return new self(
            message: 'Account name cannot be empty.',
            errorCode: ErrorCode::ACCOUNT_EMPTY_NAME,
            context: []
        );
    }

    public static function inconsistentTimestamps(DateTimeImmutable $createdAt, DateTimeImmutable $updatedAt): self
    {
        return new self(
            message: 'Account updated_at cannot be earlier than created_at.',
            errorCode: ErrorCode::ACCOUNT_INCONSISTENT_TIMESTAMPS,
            context: [
                'created_at' => $createdAt->format(DATE_ATOM),
                'updated_at' => $updatedAt->format(DATE_ATOM),
            ]
        );
    }

    public static function staleUpdate(DateTimeImmutable $currentUpdatedAt, DateTimeImmutable $newUpdatedAt): self
    {
        return new self(
            message: 'Account update timestamp cannot move backwards.',
            errorCode: ErrorCode::ACCOUNT_STALE_UPDATE,
            context: [
                'current_updated_at' => $currentUpdatedAt->format(DATE_ATOM),
                'new_updated_at' => $newUpdatedAt->format(DATE_ATOM),
            ]
        );
    }
}
