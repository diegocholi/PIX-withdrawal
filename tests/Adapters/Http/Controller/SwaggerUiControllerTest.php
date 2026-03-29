<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\Di\Container;
use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class SwaggerUiControllerTest extends TestCase
{
    public function testSwaggerUiEndpointReturnsHtmlWhenEnabled(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $container->set(
            AdvancedPluginConfig::class,
            new AdvancedPluginConfig(
                openApiTitle: 'PIX Withdrawal API',
                openApiVersion: '1.0.0',
                specPath: '/openapi.json',
                swaggerUiPath: '/docs',
                swaggerUiEnabled: true,
            )
        );
        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->get('/docs');
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('text/html; charset=utf-8', $response->getHeaderLine('content-type'));
        self::assertStringContainsString('SwaggerUIBundle', (string) $response->getBody());
        self::assertStringContainsString('/openapi.json', (string) $response->getBody());
    }

    public function testSwaggerUiEndpointReturns404WhenDisabled(): void
    {
        /** @var Container $container */
        $container = (new HyperfContainerFactory())->create();
        $container->set(
            AdvancedPluginConfig::class,
            new AdvancedPluginConfig(
                openApiTitle: 'PIX Withdrawal API',
                openApiVersion: '1.0.0',
                specPath: '/openapi.json',
                swaggerUiPath: '/docs',
                swaggerUiEnabled: false,
            )
        );
        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->get('/docs');
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(404, $response->getStatusCode());
        self::assertSame('Not Found', (string) $response->getBody());
    }
}
