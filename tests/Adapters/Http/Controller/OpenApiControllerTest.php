<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Controller;

use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class OpenApiControllerTest extends TestCase
{
    public function testOpenApiEndpointReturnsPublishedDocument(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $client = new Client($container);
        $response = null;

        Coroutine\run(static function () use ($client, &$response): void {
            $response = $client->get('/openapi.json');
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('content-type'));

        /** @var array<string, mixed> $payload */
        $payload = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('3.0.3', $payload['openapi']);
        self::assertArrayHasKey('/account/{accountId}/balance/withdraw', $payload['paths']);
        self::assertArrayHasKey('/account/{accountId}/balance/withdraw/{withdrawId}', $payload['paths']);
    }
}
