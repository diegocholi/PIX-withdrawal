<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Advanced\Config;

use Hyperf\Config\Config;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfigProvider;

final class AdvancedPluginConfigProviderTest extends TestCase
{
    public function testProviderReadsAdvancedConfigWithoutExtraNesting(): void
    {
        $provider = new AdvancedPluginConfigProvider(
            new Config([
                'advanced' => [
                    'openapi' => [
                        'title' => 'PIX Withdrawal API',
                        'version' => '1.2.3',
                        'spec_path' => '/openapi.json',
                    ],
                    'swagger_ui' => [
                        'path' => '/docs',
                        'enabled' => true,
                    ],
                ],
            ])
        );

        $config = $provider->provide();

        self::assertSame('PIX Withdrawal API', $config->openApiTitle());
        self::assertSame('1.2.3', $config->openApiVersion());
        self::assertSame('/openapi.json', $config->specPath());
        self::assertSame('/docs', $config->swaggerUiPath());
        self::assertTrue($config->swaggerUiEnabled());
    }
}
