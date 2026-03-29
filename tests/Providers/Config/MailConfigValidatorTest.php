<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Config;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfig;
use Tecnofit\PixWithdrawal\Providers\Config\MailConfigValidator;
use Tecnofit\PixWithdrawal\Providers\Config\ProviderConfigProvider;

final class MailConfigValidatorTest extends TestCase
{
    public function testAllowsDisabledMailConfigurationInTestEnvironment(): void
    {
        $validator = new MailConfigValidator($this->providerConfig('test'));

        $validator->validate(MailConfig::fromArray([
            'default' => null,
            'mailers' => [],
        ]));

        $this->addToAssertionCount(1);
    }

    public function testRequiresPasswordWhenUsernameIsProvided(): void
    {
        $validator = new MailConfigValidator($this->providerConfig('local'));
        $config = MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
                    'host' => 'mailhog',
                    'port' => 1025,
                    'username' => 'mailer',
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
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP authentication password must be configured when username is provided.');

        $validator->validate($config);
    }

    public function testRequiresPositiveTimeoutsOutsideTestEnvironment(): void
    {
        $validator = new MailConfigValidator($this->providerConfig('prod'));
        $config = MailConfig::fromArray([
            'default' => 'smtp',
            'mailers' => [
                'smtp' => [
                    'host' => 'smtp.example.test',
                    'port' => 25,
                    'from' => [
                        'address' => 'no-reply@example.test',
                        'name' => 'PIX Withdrawal',
                    ],
                    'timeouts' => [
                        'connect_seconds' => 0,
                        'read_seconds' => 5,
                    ],
                ],
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('SMTP timeout configuration must define positive connect and read timeouts.');

        $validator->validate($config);
    }

    private function providerConfig(string $environment): ProviderConfigProvider
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->method('get')->willReturnMap([
            ['providers.runtime.environment', 'prod', $environment],
        ]);

        return new ProviderConfigProvider($config);
    }
}
