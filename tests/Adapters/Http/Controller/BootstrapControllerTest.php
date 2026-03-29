<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class BootstrapControllerTest extends TestCase
{
    public function testHttpBootstrapRouteReturnsMinimalApplicationPayload(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->get('/');
        });

        self::assertInstanceOf(ResponseInterface::class, $response);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('content-type'));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'name' => 'pix-withdrawal',
                'status' => 'ok',
                'runtime' => 'http',
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }
}
