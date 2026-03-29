<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Config;

final class ProviderEnvironment
{
    public const LOCAL = 'local';
    public const TEST = 'test';
    public const PROD = 'prod';

    public static function normalize(string $applicationEnvironment): string
    {
        $normalized = strtolower(trim($applicationEnvironment));

        return match ($normalized) {
            'local', 'dev', 'development' => self::LOCAL,
            'test', 'testing' => self::TEST,
            default => self::PROD,
        };
    }
}
