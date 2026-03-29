<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Factories;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;
use Tecnofit\PixWithdrawal\Providers\Factories\SmtpConfigFactory;

final class SmtpConfigFactoryTest extends TestCase
{
    public function testCreatesMailConfigFromCentralProviderConfiguration(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', 'local'],
            ['mail', [], [
                'default' => 'smtp',
                'mailers' => [
                    'smtp' => [
                        'transport' => 'smtp',
                        'host' => 'mailhog',
                        'port' => 1025,
                        'username' => 'mailer',
                        'password' => 'secret',
                        'encryption' => null,
                        'from' => [
                            'address' => 'no-reply@example.test',
                            'name' => 'PIX Withdrawal',
                        ],
                        'timeouts' => [
                            'connect_seconds' => 3,
                            'read_seconds' => 7,
                        ],
                    ],
                ],
            ]],
        ]);

        $providerConfigProvider = new ProviderConfigProvider($config);
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::once())
            ->method('info')
            ->with(
                'providers.bootstrap.mail.loaded',
                self::callback(static function (LogContext $context): bool {
                    return $context->context()['environment'] === 'local'
                        && $context->context()['default_mailer'] === 'smtp'
                        && $context->context()['host'] === 'mailhog'
                        && $context->context()['has_authentication'] === true;
                }),
            );

        $factory = new SmtpConfigFactory(
            $providerConfigProvider,
            new MailConfigValidator($providerConfigProvider),
            $logger,
        );
        $mailConfig = $factory->create();

        self::assertInstanceOf(MailConfig::class, $mailConfig);
        self::assertSame('smtp', $mailConfig->defaultMailer());
        self::assertSame('smtp', $mailConfig->transport());
        self::assertSame('mailhog', $mailConfig->host());
        self::assertSame(1025, $mailConfig->port());
        self::assertSame('mailer', $mailConfig->username());
        self::assertSame('secret', $mailConfig->password());
        self::assertSame('no-reply@example.test', $mailConfig->fromAddress());
        self::assertSame('PIX Withdrawal', $mailConfig->fromName());
        self::assertSame(3, $mailConfig->connectTimeoutSeconds());
        self::assertSame(7, $mailConfig->readTimeoutSeconds());
    }

    public function testFailsFastWhenSenderAddressIsMissingOutsideTestEnvironment(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', 'local'],
            ['mail', [], [
                'default' => 'smtp',
                'mailers' => [
                    'smtp' => [
                        'transport' => 'smtp',
                        'host' => 'mailhog',
                        'port' => 1025,
                        'from' => [
                            'address' => '',
                            'name' => 'PIX Withdrawal',
                        ],
                        'timeouts' => [
                            'connect_seconds' => 3,
                            'read_seconds' => 7,
                        ],
                    ],
                ],
            ]],
        ]);

        $providerConfigProvider = new ProviderConfigProvider($config);
        $logger = $this->createMock(StructuredLogger::class);
        $logger->expects(self::never())->method('info');
        $factory = new SmtpConfigFactory(
            $providerConfigProvider,
            new MailConfigValidator($providerConfigProvider),
            $logger,
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP default sender address must be non-empty outside test environment.');

        $factory->create();
    }
}
