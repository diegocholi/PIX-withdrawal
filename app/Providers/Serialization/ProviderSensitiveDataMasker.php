<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Serialization;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;

final class ProviderSensitiveDataMasker implements SensitiveDataMasker
{
    /**
     * @var list<string>
     */
    private const SENSITIVE_SCALAR_KEYS = [
        'authorization',
        'token',
        'access_token',
        'refresh_token',
        'password',
    ];

    public function maskPixKey(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (str_contains($value, '@')) {
            [$localPart, $domain] = explode('@', $value, 2);
            $prefix = substr($localPart, 0, 1);

            return sprintf('%s***@%s', $prefix === false ? '*' : $prefix, $domain);
        }

        return '***';
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function maskPayload(array $payload): array
    {
        $masked = [];

        foreach ($payload as $key => $value) {
            $masked[$key] = $this->maskValue($key, $value);
        }

        return $masked;
    }

    private function maskValue(string $key, mixed $value): mixed
    {
        if (is_array($value)) {
            return $this->maskNestedPayload($key, $value);
        }

        if (! is_string($value)) {
            return $value;
        }

        if ($this->shouldMaskScalar($key)) {
            return $this->maskPixKey($value);
        }

        return $value;
    }

    /**
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    private function maskNestedPayload(string $key, array $payload): array
    {
        if ($this->isPixPayload($key)) {
            return $this->maskPixPayload($payload);
        }

        $masked = [];

        foreach ($payload as $nestedKey => $value) {
            $normalizedKey = is_string($nestedKey) ? $nestedKey : (string) $nestedKey;
            $masked[$nestedKey] = $this->maskValue($normalizedKey, $value);
        }

        return $masked;
    }

    /**
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    private function maskPixPayload(array $payload): array
    {
        $masked = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key) ? $key : (string) $key;

            if ($normalizedKey === 'key' && is_string($value)) {
                $masked[$key] = $this->maskPixKey($value);
                continue;
            }

            $masked[$key] = $this->maskValue($normalizedKey, $value);
        }

        return $masked;
    }

    private function shouldMaskScalar(string $key): bool
    {
        $normalizedKey = strtolower(trim($key));

        return $normalizedKey === 'pix_key'
            || in_array($normalizedKey, self::SENSITIVE_SCALAR_KEYS, true);
    }

    private function isPixPayload(string $key): bool
    {
        return strtolower(trim($key)) === 'pix';
    }
}
