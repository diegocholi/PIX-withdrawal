<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Health;

use Hyperf\Contract\ConfigInterface;

final readonly class ReadinessProbe
{
    public function __construct(private ConfigInterface $config)
    {
    }

    public function isReady(): bool
    {
        return $this->hasApplicationConfiguration()
            && $this->hasHttpServerConfiguration()
            && $this->hasProviderConfiguration('databases')
            && $this->hasProviderConfiguration('kafka')
            && $this->hasProviderConfiguration('mail')
            && $this->hasWritableRuntimeDirectories();
    }

    private function hasApplicationConfiguration(): bool
    {
        $applicationName = trim((string) $this->config->get('app.name', ''));
        $applicationEnvironment = trim((string) $this->config->get('app.env', ''));

        return $applicationName !== '' && $applicationEnvironment !== '';
    }

    private function hasHttpServerConfiguration(): bool
    {
        $servers = $this->config->get('server.servers', []);

        if (! is_array($servers)) {
            return false;
        }

        foreach ($servers as $server) {
            if (! is_array($server)) {
                continue;
            }

            if (($server['name'] ?? null) !== 'http') {
                continue;
            }

            $port = $server['port'] ?? null;

            return is_int($port) && $port > 0;
        }

        return false;
    }

    private function hasProviderConfiguration(string $key): bool
    {
        return is_array($this->config->get($key));
    }

    private function hasWritableRuntimeDirectories(): bool
    {
        foreach ([BASE_PATH . '/runtime', BASE_PATH . '/storage'] as $directory) {
            if (! is_dir($directory) || ! is_writable($directory)) {
                return false;
            }
        }

        return true;
    }
}
