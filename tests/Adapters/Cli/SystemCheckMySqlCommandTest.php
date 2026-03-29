<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Tests\Plugins\Data\Migrations\DataSchemaMigrationIntegrationTestCase;

final class SystemCheckMySqlCommandTest extends DataSchemaMigrationIntegrationTestCase
{
    public function testCommandConfirmsMySqlConnectivityAndMinimalQueryExecution(): void
    {
        $application = $this->container->get(ApplicationInterface::class);
        $command = $application->find('system:check:mysql');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('system:check:mysql', $payload['command']);
        self::assertSame('mysql', $payload['check']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('default', $payload['connection']);
        self::assertSame('mysql', $payload['driver']);
        self::assertSame('mysql', $payload['host']);
        self::assertSame(3306, $payload['port']);
        self::assertSame('pix_withdrawal', $payload['database']);
        self::assertSame(1, $payload['ping']);
        self::assertIsString($payload['server_version']);
        self::assertNotSame('', $payload['server_version']);
    }
}
