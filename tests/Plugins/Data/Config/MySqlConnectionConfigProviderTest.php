<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Config;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigProvider;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigValidator;

final class MySqlConnectionConfigProviderTest extends TestCase
{
    public function testLoadsAndValidatesDefaultConnectionFromFrameworkConfiguration(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['databases.default_connection', null, 'default'],
            ['databases.default', null, [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => 3306,
                'database' => 'pix_withdrawal',
                'username' => 'pix_withdrawal',
                'password' => 'secret',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]],
            ['databases.connections.default', [], [
                'driver' => 'mysql',
                'host' => 'mysql',
                'port' => 3306,
                'database' => 'pix_withdrawal',
                'username' => 'pix_withdrawal',
                'password' => 'secret',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]],
        ]);

        $provider = new MySqlConnectionConfigProvider($config, new MySqlConnectionConfigValidator());
        $connection = $provider->defaultConnection();

        self::assertSame('default', $provider->defaultConnectionName());
        self::assertSame('mysql', $connection->driver());
        self::assertSame('mysql', $connection->host());
        self::assertSame(3306, $connection->port());
    }
}
