<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Response;

use Hyperf\HttpMessage\Server\Response as HyperfPsrResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ErrorResponseFactory;

final class ErrorResponseFactoryTest extends TestCase
{
    public function testCreateBuildsJsonErrorResponseWithCorrelationId(): void
    {
        $factory = new ErrorResponseFactory($this->jsonResponseStub());

        $response = $factory->create(
            code: 'withdraw.not_found',
            message: 'Withdraw was not found for processing.',
            status: 404,
            details: ['withdraw_id' => 'wd_123'],
            correlationId: 'corr_123',
        );

        self::assertSame(404, $response->getStatusCode());
        self::assertSame('corr_123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'withdraw.not_found',
                    'message' => 'Withdraw was not found for processing.',
                    'details' => [
                        'withdraw_id' => 'wd_123',
                    ],
                ],
                'meta' => [
                    'correlation_id' => 'corr_123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testCreateBuildsJsonErrorResponseWithoutCorrelationIdHeaderWhenMissing(): void
    {
        $factory = new ErrorResponseFactory($this->jsonResponseStub());

        $response = $factory->create(
            code: 'http.internal_server_error',
            message: 'Internal server error.',
            status: 500,
        );

        self::assertSame(500, $response->getStatusCode());
        self::assertSame('', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'error' => [
                    'code' => 'http.internal_server_error',
                    'message' => 'Internal server error.',
                    'details' => [],
                ],
                'meta' => [
                    'correlation_id' => null,
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
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
