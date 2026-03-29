<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Data\Config;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigValidator;

final class MySqlConnectionConfigValidatorTest extends TestCase
{
    public function testAcceptsValidMySqlConfiguration(): void
    {
        $validator = new MySqlConnectionConfigValidator();

        $validator->validate(MySqlConnectionConfig::fromArray('default', [
            'driver' => 'mysql',
            'host' => 'mysql',
            'port' => 3306,
            'database' => 'pix_withdrawal',
            'username' => 'pix_withdrawal',
            'password' => 'secret',
        ]));

        self::assertTrue(true);
    }

    public function testRejectsInvalidDriver(): void
    {
        $validator = new MySqlConnectionConfigValidator();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MySQL connection driver must be "mysql".');

        $validator->validate(MySqlConnectionConfig::fromArray('default', [
            'driver' => 'pgsql',
            'host' => 'mysql',
            'port' => 3306,
            'database' => 'pix_withdrawal',
            'username' => 'pix_withdrawal',
            'password' => 'secret',
        ]));
    }
}
