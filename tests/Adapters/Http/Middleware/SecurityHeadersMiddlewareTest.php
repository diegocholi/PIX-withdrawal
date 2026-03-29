<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Middleware;

use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class SecurityHeadersMiddlewareTest extends TestCase
{
    public function testHttpResponsesIncludeCanonicalSecurityHeaders(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->get('/health');
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame('no-store, no-cache, must-revalidate', $response->getHeaderLine('Cache-Control'));
        self::assertSame('no-cache', $response->getHeaderLine('Pragma'));
        self::assertSame('no-referrer', $response->getHeaderLine('Referrer-Policy'));
        self::assertSame('nosniff', $response->getHeaderLine('X-Content-Type-Options'));
        self::assertSame('DENY', $response->getHeaderLine('X-Frame-Options'));
    }
}
