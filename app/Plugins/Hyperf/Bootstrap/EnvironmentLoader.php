<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap;

use Hyperf\Support\DotenvManager;

final class EnvironmentLoader
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function load(bool $force = false): void
    {
        if (! is_file($this->environmentFilePath())) {
            return;
        }

        if ($force) {
            DotenvManager::reload([$this->basePath], true);

            return;
        }

        DotenvManager::load([$this->basePath]);
    }

    private function environmentFilePath(): string
    {
        return $this->basePath . '/.env';
    }
}
