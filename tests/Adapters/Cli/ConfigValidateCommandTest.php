<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class ConfigValidateCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=config-validate@pix.local');
        putenv('MAIL_FROM_NAME=Config Validate');
        putenv('MAIL_CONNECT_TIMEOUT_SECONDS=7');
        putenv('MAIL_READ_TIMEOUT_SECONDS=9');
        putenv('WITHDRAW_DUPLICATE_GUARD_WINDOW_SECONDS=75');
    }

    protected function tearDown(): void
    {
        foreach ([
            'APP_ENV',
            'KAFKA_BROKERS',
            'MAIL_HOST',
            'MAIL_PORT',
            'MAIL_FROM_ADDRESS',
            'MAIL_FROM_NAME',
            'MAIL_CONNECT_TIMEOUT_SECONDS',
            'MAIL_READ_TIMEOUT_SECONDS',
            'WITHDRAW_DUPLICATE_GUARD_WINDOW_SECONDS',
        ] as $environmentVariable) {
            putenv($environmentVariable);
        }

        parent::tearDown();
    }

    public function testCommandValidatesCriticalConfigurationFromTypedObjectsAndRuntimeBindings(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:config:validate');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('app:config:validate', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertSame(['total' => 5, 'ok' => 5, 'failed' => 0], $payload['summary']);
        self::assertSame('ok', $payload['checks']['providers']['status']);
        self::assertSame('local', $payload['checks']['providers']['environment']);
        self::assertSame(75, $payload['checks']['providers']['withdraw_duplicate_guard_window_seconds']);
        self::assertSame('ok', $payload['checks']['runtimes']['status']);
        self::assertSame(4, $payload['checks']['runtimes']['runtime_count']);
        self::assertSame('ok', $payload['checks']['mysql']['status']);
        self::assertSame('default', $payload['checks']['mysql']['connection']);
        self::assertSame('***', $payload['checks']['mysql']['username']);
        self::assertSame('ok', $payload['checks']['kafka']['status']);
        self::assertSame(1, $payload['checks']['kafka']['brokers_count']);
        self::assertSame('ok', $payload['checks']['mail']['status']);
        self::assertSame('mailhog', $payload['checks']['mail']['host']);
        self::assertSame('c***@pix.local', $payload['checks']['mail']['from_address']);
        self::assertSame(7, $payload['checks']['mail']['connect_timeout_seconds']);
        self::assertSame(9, $payload['checks']['mail']['read_timeout_seconds']);
    }
}
