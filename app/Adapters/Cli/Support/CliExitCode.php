<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

final class CliExitCode
{
    public const SUCCESS = 0;
    public const INVALID_ARGUMENT = 2;
    public const DEPENDENCY_FAILURE = 3;
    public const PARTIAL_FAILURE = 4;
    public const RUNTIME_FAILURE = 5;
    public const INTERRUPTED = 130;

    /**
     * @return list<int>
     */
    public static function all(): array
    {
        return [
            self::SUCCESS,
            self::INVALID_ARGUMENT,
            self::DEPENDENCY_FAILURE,
            self::PARTIAL_FAILURE,
            self::RUNTIME_FAILURE,
            self::INTERRUPTED,
        ];
    }
}
