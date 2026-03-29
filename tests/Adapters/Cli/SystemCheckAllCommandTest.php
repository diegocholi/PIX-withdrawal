<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Tests\Providers\Mail\MailhogIntegrationTestCase;

final class SystemCheckAllCommandTest extends MailhogIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=no-reply@pix-withdrawal.local');
        putenv('MAIL_FROM_NAME=PIX Withdrawal');
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('KAFKA_BROKERS');
        putenv('MAIL_HOST');
        putenv('MAIL_PORT');
        putenv('MAIL_FROM_ADDRESS');
        putenv('MAIL_FROM_NAME');

        parent::tearDown();
    }

    public function testCommandAggregatesMainEnvironmentChecks(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('system:check:all');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $mailPayload = $payload['checks']['mail'];
        $message = $this->waitForMailhogMessage($mailPayload['recipient'], $mailPayload['subject']);

        self::assertSame(0, $exitCode);
        self::assertSame('system:check:all', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertSame(['total' => 3, 'ok' => 3, 'failed' => 0], $payload['summary']);
        self::assertSame('ok', $payload['checks']['mysql']['status']);
        self::assertSame('default', $payload['checks']['mysql']['connection']);
        self::assertSame(1, $payload['checks']['mysql']['ping']);
        self::assertSame('ok', $payload['checks']['kafka']['status']);
        self::assertSame(1, $payload['checks']['kafka']['configured_brokers_count']);
        self::assertGreaterThanOrEqual(1, $payload['checks']['kafka']['discovered_brokers_count']);
        self::assertSame('ok', $payload['checks']['mail']['status']);
        self::assertSame('mailhog', $payload['checks']['mail']['host']);
        self::assertSame('n***@pix-withdrawal.local', $payload['checks']['mail']['from_address']);
        self::assertNotNull($message, 'MailHog did not receive the aggregated CLI diagnostic email within the expected timeout.');
        self::assertSame([$mailPayload['recipient']], $message['Raw']['To']);
        self::assertSame($mailPayload['subject'], $message['Content']['Headers']['Subject'][0] ?? null);
    }
}
