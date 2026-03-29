<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Response;

use Hyperf\HttpMessage\Server\Response as HyperfPsrResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;

final class SuccessResponseFactoryTest extends TestCase
{
    public function testCreateBuildsJsonSuccessResponseWithCanonicalCorrelationHeader(): void
    {
        $factory = new SuccessResponseFactory($this->jsonResponseStub());

        $response = $factory->create([
            'data' => [
                'status' => 'processing',
            ],
            'meta' => [
                'correlation_id' => 'corr_123',
            ],
        ]);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('corr_123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('content-type'));
    }

    public function testCreateSupportsCustomStatusAndHeaders(): void
    {
        $factory = new SuccessResponseFactory($this->jsonResponseStub());

        $response = $factory->create(
            payload: ['status' => 'ok'],
            status: 202,
            headers: [ApiHeader::LOCATION => '/health']
        );

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('/health', $response->getHeaderLine(ApiHeader::LOCATION));
        self::assertSame('', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
    }

    private function jsonResponseStub(): ResponseInterface
    {
        $response = $this->createMock(ResponseInterface::class);
        $response
            ->method('json')
            ->willReturnCallback(static function (array $payload): HyperfPsrResponse {
                return (new HyperfPsrResponse())
                    ->withHeader('content-type', 'application/json; charset=utf-8')
                    ->withBody(new SwooleStream(json_encode($payload, JSON_THROW_ON_ERROR)));
            });

        return $response;
    }
}
