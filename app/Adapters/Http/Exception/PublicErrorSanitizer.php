<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Exception;

final class PublicErrorSanitizer
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_TERMS = [
        'stack',
        'stacktrace',
        'trace',
        'sql',
        'mysql',
        'database',
        'pdo',
        'query',
        'kafka',
        'broker',
        'queue',
        'provider',
        'repository',
        'adapter',
        'plugin',
        'framework',
        'driver',
        'table',
        'column',
        'syntax error',
        'exception class',
    ];

    /**
     * @var list<string>
     */
    private const SENSITIVE_DETAIL_KEYS = [
        'stack',
        'stacktrace',
        'trace',
        'trace_id',
        'sql',
        'query',
        'exception',
        'exception_class',
        'class',
        'file',
        'line',
        'provider',
        'plugin',
        'adapter',
        'repository',
        'database',
        'db',
        'mysql',
        'kafka',
        'broker',
        'queue',
        'table',
        'column',
    ];

    /**
     * @param array<string, mixed> $details
     * @return array{message: string, details: array<string, mixed>}
     */
    public function sanitize(int $status, string $message, array $details): array
    {
        if ($status >= 500) {
            return [
                'message' => 'Internal server error.',
                'details' => [],
            ];
        }

        $sanitizedMessage = $this->sanitizeMessage($status, $message);
        $sanitizedDetails = $this->sanitizeDetails($details);

        return [
            'message' => $sanitizedMessage,
            'details' => $sanitizedDetails,
        ];
    }

    private function sanitizeMessage(int $status, string $message): string
    {
        $normalized = trim($message);

        if ($normalized === '') {
            return $this->fallbackMessage($status);
        }

        if (! $this->containsSensitiveTerm($normalized)) {
            return $normalized;
        }

        return $this->fallbackMessage($status);
    }

    /**
     * @param array<string, mixed> $details
     * @return array<string, mixed>
     */
    private function sanitizeDetails(array $details): array
    {
        $sanitized = [];

        foreach ($details as $key => $value) {
            $normalizedKey = trim((string) $key);

            if ($normalizedKey === '' || $this->isSensitiveDetailKey($normalizedKey)) {
                continue;
            }

            $sanitizedValue = $this->sanitizeValue($value);

            if ($sanitizedValue === null) {
                continue;
            }

            $sanitized[$normalizedKey] = $sanitizedValue;
        }

        return $sanitized;
    }

    private function sanitizeValue(mixed $value): mixed
    {
        if ($value === null || is_int($value) || is_float($value) || is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            $normalized = trim($value);

            if ($normalized === '' || $this->containsSensitiveTerm($normalized)) {
                return null;
            }

            return $normalized;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                $items = [];

                foreach ($value as $item) {
                    $sanitizedItem = $this->sanitizeValue($item);

                    if ($sanitizedItem !== null) {
                        $items[] = $sanitizedItem;
                    }
                }

                return $items === [] ? null : $items;
            }

            $sanitized = $this->sanitizeDetails($value);

            return $sanitized === [] ? null : $sanitized;
        }

        return null;
    }

    private function containsSensitiveTerm(string $value): bool
    {
        $normalized = strtolower($value);

        foreach (self::SENSITIVE_TERMS as $term) {
            if (str_contains($normalized, $term)) {
                return true;
            }
        }

        return false;
    }

    private function isSensitiveDetailKey(string $key): bool
    {
        return in_array(strtolower($key), self::SENSITIVE_DETAIL_KEYS, true);
    }

    private function fallbackMessage(int $status): string
    {
        return match ($status) {
            404 => 'Requested resource was not found.',
            409 => 'Request could not be processed in the current state.',
            422 => 'Request validation failed.',
            default => 'Request could not be processed.',
        };
    }
}
