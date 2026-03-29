<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Middleware;

use Hyperf\HttpMessage\Server\Request;
use Hyperf\HttpMessage\Server\Response;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\RequestLoggingMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class RequestLoggingMiddlewareTest extends TestCase
{
    public function testMiddlewareLogsInboundCreateWithdrawRequestUsingCorrelationAttribute(): void
    {
        $observability = new InMemoryObservability();
        $middleware = new RequestLoggingMiddleware($observability, new \Tecnofit\PixWithdrawal\Adapters\Http\Middleware\HttpLogContextResolver());
        $request = (new Request('POST', '/account/acc_123/balance/withdraw'))
            ->withAttribute(CorrelationIdMiddleware::ATTRIBUTE, 'corr_123')
            ->withHeader(ApiHeader::CONTENT_TYPE, 'application/json')
            ->withHeader(ApiHeader::ACCEPT, 'application/json')
            ->withHeader('User-Agent', 'phpunit')
            ->withHeader('Content-Length', '128')
            ->withServerParams(['remote_addr' => '127.0.0.1'])
            ->withParsedBody([
                'amount' => '10.00',
                'pix' => [
                    'key' => 'user@example.com',
                ],
            ]);

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        });

        self::assertInstanceOf(ResponseInterface::class, $response);
        self::assertCount(1, $observability->infoCalls);
        self::assertSame('http.request.received', $observability->infoCalls[0]['message']);

        $context = $observability->infoCalls[0]['context']->toArray();

        self::assertSame('corr_123', $context['correlation_id']);
        self::assertSame('acc_123', $context['account_id']);
        self::assertNull($context['withdraw_id']);
        self::assertSame(
            [
                'transport' => 'http',
                'direction' => 'inbound',
            ],
            $context['trace_metadata']
        );
        self::assertSame(
            [
                'method' => 'POST',
                'path' => '/account/acc_123/balance/withdraw',
                'expected_status_code' => 202,
                'content_type' => 'application/json',
                'accept' => 'application/json',
                'user_agent' => 'phpunit',
                'remote_addr' => '127.0.0.1',
                'has_body' => true,
            ],
            $context['context']
        );
        self::assertArrayNotHasKey('body', $context['context']);
        self::assertArrayNotHasKey('pix', $context['context']);
    }

    public function testMiddlewareFallsBackToCorrelationHeaderWhenAttributeIsMissing(): void
    {
        $observability = new InMemoryObservability();
        $middleware = new RequestLoggingMiddleware($observability, new \Tecnofit\PixWithdrawal\Adapters\Http\Middleware\HttpLogContextResolver());
        $request = (new Request('GET', '/account/acc_123/balance/withdraw/wd_123'))
            ->withHeader(ApiHeader::CORRELATION_ID, 'corr_from_header');

        $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        });

        $context = $observability->infoCalls[0]['context']->toArray();

        self::assertSame('corr_from_header', $context['correlation_id']);
        self::assertSame('acc_123', $context['account_id']);
        self::assertSame('wd_123', $context['withdraw_id']);
        self::assertSame(200, $context['context']['expected_status_code']);
        self::assertFalse($context['context']['has_body']);
    }
}

final class InMemoryObservability implements Observability
{
    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $infoCalls = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoCalls[] = [
            'message' => $message,
            'context' => $context,
        ];
    }

    public function warning(string $message, LogContext $context): void
    {
    }

    public function error(string $message, LogContext $context): void
    {
    }

    public function increment(MetricPoint $metricPoint): void
    {
    }
}
