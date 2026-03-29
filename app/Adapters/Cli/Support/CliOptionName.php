<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

final class CliOptionName
{
    public const CORRELATION_ID = 'correlation-id';
    public const DRY_RUN = 'dry-run';
    public const BATCH_SIZE = 'batch-size';
    public const MAX_MESSAGES = 'max-messages';
    public const IDLE_TIMEOUT = 'idle-timeout';
    public const GROUP_ID = 'group-id';
    public const TOPIC = 'topic';
    public const POLL_TIMEOUT = 'poll-timeout';
    public const FORCE = 'force';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::CORRELATION_ID,
            self::DRY_RUN,
            self::BATCH_SIZE,
            self::MAX_MESSAGES,
            self::IDLE_TIMEOUT,
            self::GROUP_ID,
            self::TOPIC,
            self::POLL_TIMEOUT,
            self::FORCE,
        ];
    }
}
