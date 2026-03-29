<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Health;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Health\ReadinessProbe;

final class ReadinessProbeTest extends TestCase
{
    public function testProbeReportsReadyWhenMinimalRuntimeConfigurationIsAvailable(): void
    {
        $probe = new ReadinessProbe($this->config([
            'app.name' => 'pix-withdrawal',
            'app.env' => 'test',
            'server.servers' => [
                [
                    'name' => 'http',
                    'port' => 9501,
                ],
            ],
            'databases' => ['default' => null, 'connections' => []],
            'kafka' => ['brokers' => []],
            'mail' => ['default' => null, 'mailers' => []],
        ]));

        self::assertTrue($probe->isReady());
    }

    public function testProbeReportsNotReadyWhenHttpServerConfigurationIsMissing(): void
    {
        $probe = new ReadinessProbe($this->config([
            'app.name' => 'pix-withdrawal',
            'app.env' => 'test',
            'server.servers' => [],
            'databases' => ['default' => null, 'connections' => []],
            'kafka' => ['brokers' => []],
            'mail' => ['default' => null, 'mailers' => []],
        ]));

        self::assertFalse($probe->isReady());
    }

    /**
     * @param array<string, mixed> $values
     */
    private function config(array $values): ConfigInterface
    {
        return new class($values) implements ConfigInterface {
            /**
             * @param array<string, mixed> $values
             */
            public function __construct(private array $values)
            {
            }

            public function get(string $key, mixed $default = null): mixed
            {
                return $this->values[$key] ?? $default;
            }

            public function has(string $key): bool
            {
                return array_key_exists($key, $this->values);
            }

            public function set(string $key, mixed $value): void
            {
                $this->values[$key] = $value;
            }
        };
    }
}
