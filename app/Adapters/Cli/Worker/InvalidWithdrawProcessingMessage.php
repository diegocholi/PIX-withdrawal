<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

final class InvalidWithdrawProcessingMessage extends \InvalidArgumentException
{
    public static function unsupportedEvent(string $eventName): self
    {
        return new self(sprintf(
            'Withdraw processing worker supports only "withdraw.queued" messages, got "%s".',
            $eventName,
        ));
    }
}
