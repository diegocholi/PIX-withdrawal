<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Response;

use Hyperf\HttpMessage\Server\Response as HyperfPsrResponse;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Contract\ResponseInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\WithdrawAcceptedResponse;

final class WithdrawAcceptedResponseTest extends TestCase
{
    public function testCreateBuildsImmediateAcceptedResponseWithCanonicalHeadersAndPayload(): void
    {
        $builder = new WithdrawAcceptedResponse(new SuccessResponseFactory($this->jsonResponseStub()));

        $response = $builder->create([
            'data' => [
                'withdraw_id' => 'wd_123',
                'account_id' => 'acc_123',
                'status' => 'queued',
                'scheduled' => false,
                'scheduled_for' => null,
                'status_url' => '/account/acc_123/balance/withdraw/wd_123',
            ],
            'meta' => [
                'correlation_id' => 'corr_123',
            ],
        ]);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('application/json; charset=utf-8', $response->getHeaderLine('content-type'));
        self::assertSame('corr_123', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('/account/acc_123/balance/withdraw/wd_123', $response->getHeaderLine(ApiHeader::LOCATION));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_123',
                    'account_id' => 'acc_123',
                    'status' => 'queued',
                    'scheduled' => false,
                    'scheduled_for' => null,
                    'status_url' => '/account/acc_123/balance/withdraw/wd_123',
                ],
                'meta' => [
                    'correlation_id' => 'corr_123',
                ],
            ], JSON_THROW_ON_ERROR),
            (string) $response->getBody()
        );
    }

    public function testCreateBuildsScheduledAcceptedResponseWithCanonicalHeadersAndPayload(): void
    {
        $builder = new WithdrawAcceptedResponse(new SuccessResponseFactory($this->jsonResponseStub()));

        $response = $builder->create([
            'data' => [
                'withdraw_id' => 'wd_456',
                'account_id' => 'acc_123',
                'status' => 'scheduled',
                'scheduled' => true,
                'scheduled_for' => '2026-04-01T12:00:00+00:00',
                'status_url' => '/account/acc_123/balance/withdraw/wd_456',
            ],
            'meta' => [
                'correlation_id' => 'corr_456',
            ],
        ]);

        self::assertSame(202, $response->getStatusCode());
        self::assertSame('corr_456', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
        self::assertSame('/account/acc_123/balance/withdraw/wd_456', $response->getHeaderLine(ApiHeader::LOCATION));
        self::assertJsonStringEqualsJsonString(
            json_encode([
                'data' => [
                    'withdraw_id' => 'wd_456',
                    'account_id' => 'acc_123',
                    'status' => 'scheduled',
                    'scheduled' => true,
                    'scheduled_for' => '2026-04-01T12:00:00+00:00',
                    'status_url' => '/account/acc_123/balance/withdraw/wd_456',
                ],
                'meta' => [
                    'correlation_id' => 'corr_456',
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
