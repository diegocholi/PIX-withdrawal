<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;

final readonly class CliOutputSanitizer
{
    /**
     * @var list<string>
     */
    private const CLI_SENSITIVE_KEYS = [
        'from_address',
        'username',
    ];

    public function __construct(private SensitiveDataMasker $sensitiveDataMasker)
    {
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function sanitize(array $payload): array
    {
        /** @var array<string, mixed> $maskedPayload */
        $maskedPayload = $this->sensitiveDataMasker->maskPayload($payload);

        return $this->sanitizeNested($maskedPayload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function encodeJson(array $payload): string
    {
        return json_encode($this->sanitize($payload), JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<mixed> $payload
     * @return array<mixed>
     */
    private function sanitizeNested(array $payload): array
    {
        $sanitized = [];

        foreach ($payload as $key => $value) {
            $normalizedKey = is_string($key) ? strtolower(trim($key)) : (string) $key;

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeNested($value);
                continue;
            }

            if (is_string($value) && in_array($normalizedKey, self::CLI_SENSITIVE_KEYS, true)) {
                $sanitized[$key] = $this->sensitiveDataMasker->maskPixKey($value);
                continue;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }
}
