<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Data\Seeds;

use DateTimeImmutable;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final readonly class SeedExecutionTracker
{
    public function __construct(private MySqlConnectionConfig $connectionConfig)
    {
    }

    public function markAsStarted(string $seedName, DateTimeImmutable $executedAt): bool
    {
        return $this->query()->insertOrIgnore([
            'seed_name' => trim($seedName),
            'executed_at' => $executedAt->format('Y-m-d H:i:s.u'),
        ]) === 1;
    }

    public function hasExecuted(string $seedName): bool
    {
        return $this->query()
            ->where('seed_name', trim($seedName))
            ->exists();
    }

    private function query(): Builder
    {
        return Db::connection($this->connectionConfig->name())->table('seed_execution');
    }
}
