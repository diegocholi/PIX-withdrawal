<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared\Exception;

use Tecnofit\PixWithdrawal\Core\Shared\ErrorCode;

final class InvalidTraceContext extends CoreException
{
    public static function emptyCorrelationId(): self
    {
        return new self(
            message: 'Trace correlation_id cannot be empty.',
            errorCode: ErrorCode::TRACE_EMPTY_CORRELATION_ID,
        );
    }

    public static function invalidMetadataKey(string $path): self
    {
        return new self(
            message: 'Trace metadata key cannot be empty.',
            errorCode: ErrorCode::TRACE_INVALID_METADATA_KEY,
            context: ['path' => $path]
        );
    }

    public static function unsupportedMetadataValue(string $path, string $type): self
    {
        return new self(
            message: 'Trace metadata value must be scalar, null or array.',
            errorCode: ErrorCode::TRACE_UNSUPPORTED_METADATA_VALUE,
            context: [
                'path' => $path,
                'type' => $type,
            ]
        );
    }
}
