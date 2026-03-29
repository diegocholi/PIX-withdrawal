<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Config;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;

final class MySqlConnectionConfigTest extends TestCase
{
    public function testFromArrayNormalizesTypedConfiguration(): void
    {
        $config = MySqlConnectionConfig::fromArray('default', [
            'driver' => 'mysql',
            'host' => 'mysql',
            'port' => '3306',
            'database' => 'pix_withdrawal',
            'username' => 'pix_withdrawal',
            'password' => 'secret',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
        ]);

        self::assertSame('default', $config->name());
        self::assertSame('mysql', $config->driver());
        self::assertSame('mysql', $config->host());
        self::assertSame(3306, $config->port());
        self::assertSame('pix_withdrawal', $config->database());
        self::assertSame('pix_withdrawal', $config->username());
        self::assertSame('secret', $config->password());
        self::assertSame(
            [
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
            ],
            $config->toArray()
        );
    }
}
