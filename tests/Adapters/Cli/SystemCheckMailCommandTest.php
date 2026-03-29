<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Tests\Providers\Mail\MailhogIntegrationTestCase;

final class SystemCheckMailCommandTest extends MailhogIntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=local');
        putenv('MAIL_HOST=mailhog');
        putenv('MAIL_PORT=1025');
        putenv('MAIL_FROM_ADDRESS=no-reply@pix-withdrawal.local');
        putenv('MAIL_FROM_NAME=PIX Withdrawal');
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('MAIL_HOST');
        putenv('MAIL_PORT');
        putenv('MAIL_FROM_ADDRESS');
        putenv('MAIL_FROM_NAME');

        parent::tearDown();
    }

    public function testCommandConfirmsSmtpConnectivityAndDeliversDiagnosticMessageToMailhog(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('system:check:mail');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);
        $message = $this->waitForMailhogMessage($payload['recipient'], $payload['subject']);

        self::assertSame(0, $exitCode);
        self::assertSame('system:check:mail', $payload['command']);
        self::assertSame('mail', $payload['check']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('smtp', $payload['default_mailer']);
        self::assertSame('smtp', $payload['transport']);
        self::assertSame('mailhog', $payload['host']);
        self::assertSame(1025, $payload['port']);
        self::assertSame('n***@pix-withdrawal.local', $payload['from_address']);
        self::assertStringContainsString('mail-check-', $payload['recipient']);
        self::assertSame('PIX Withdrawal CLI Mail Check', $payload['subject']);
        self::assertNotNull($message, 'MailHog did not receive the CLI diagnostic email within the expected timeout.');
        self::assertSame([$payload['recipient']], $message['Raw']['To']);
        self::assertSame($payload['subject'], $message['Content']['Headers']['Subject'][0] ?? null);
        self::assertStringContainsString(
            'Minimal SMTP diagnostic message sent by system:check:mail.',
            (string) ($message['Content']['Body'] ?? ''),
        );
    }
}
