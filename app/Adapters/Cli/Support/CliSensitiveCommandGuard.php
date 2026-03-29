<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Support;

final class CliSensitiveCommandGuard
{
    public function missingForceMessage(string $commandName, bool $force, bool $dryRun = false): ?string
    {
        if ($force || $dryRun) {
            return null;
        }

        return sprintf(
            'The command "%s" changes application state and requires --%s. Use --%s to inspect the batch without side effects.',
            trim($commandName),
            CliOptionName::FORCE,
            CliOptionName::DRY_RUN,
        );
    }
}
