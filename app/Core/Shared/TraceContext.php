<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Shared;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Traceable;
use Tecnofit\PixWithdrawal\Core\Shared\Exception\InvalidTraceContext;

final readonly class TraceContext implements SerializableDto, Traceable
{
    /**
     * @param array<string, mixed> $traceMetadata
     */
    private array $traceMetadata;
    private string $correlationId;

    /**
     * @param array<string, mixed> $traceMetadata
     */
    public function __construct(
        string $correlationId,
        array $traceMetadata = [],
    ) {
        $this->correlationId = trim($correlationId);

        if ($this->correlationId === '') {
            throw InvalidTraceContext::emptyCorrelationId();
        }

        $this->traceMetadata = self::normalizeMetadata($traceMetadata);
    }

    public function correlationId(): string
    {
        return $this->correlationId;
    }

    /**
     * @return array<string, mixed>
     */
    public function traceMetadata(): array
    {
        return $this->traceMetadata;
    }

    public function toArray(): array
    {
        return [
            'correlation_id' => $this->correlationId(),
            'trace_metadata' => $this->traceMetadata(),
        ];
    }

    /**
     * @param array<string, mixed> $traceMetadata
     * @return array<string, mixed>
     */
    public static function normalizeMetadata(array $traceMetadata): array
    {
        /** @var array<string, mixed> $normalized */
        $normalized = self::normalizeArray($traceMetadata, 'trace_metadata');

        return $normalized;
    }

    /**
     * @param array<mixed> $values
     * @return array<mixed>
     */
    private static function normalizeArray(array $values, string $path): array
    {
        $normalized = [];

        if (array_is_list($values)) {
            foreach ($values as $index => $value) {
                $normalized[$index] = self::normalizeValue($value, sprintf('%s.%d', $path, $index));
            }

            return $normalized;
        }

        foreach ($values as $key => $value) {
            $normalizedKey = trim((string) $key);

            if ($normalizedKey === '') {
                throw InvalidTraceContext::invalidMetadataKey($path);
            }

            $normalized[$normalizedKey] = self::normalizeValue($value, sprintf('%s.%s', $path, $normalizedKey));
        }

        /** @var array<string, mixed> $normalized */
        return $normalized;
    }

    private static function normalizeValue(mixed $value, string $path): mixed
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        if (is_array($value)) {
            return self::normalizeArray($value, $path);
        }

        throw InvalidTraceContext::unsupportedMetadataValue($path, get_debug_type($value));
    }
}
