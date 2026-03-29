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
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\HttpLogContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\ResponseLoggingMiddleware;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class ResponseLoggingMiddlewareTest extends TestCase
{
    public function testMiddlewareLogsSuccessfulResponseAsInfo(): void
    {
        $observability = new InMemoryResponseObservability();
        $middleware = new ResponseLoggingMiddleware($observability, new HttpLogContextResolver());
        $request = (new Request('GET', '/account/acc_123/balance/withdraw/wd_123'))
            ->withAttribute(CorrelationIdMiddleware::ATTRIBUTE, 'corr_123');

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStatus(200);
            }
        });

        self::assertSame(200, $response->getStatusCode());
        self::assertCount(1, $observability->infoCalls);
        self::assertSame('http.response.sent', $observability->infoCalls[0]['message']);

        $context = $observability->infoCalls[0]['context']->toArray();

        self::assertSame('corr_123', $context['correlation_id']);
        self::assertSame('wd_123', $context['withdraw_id']);
        self::assertSame('200', $context['status']);
        self::assertSame(false, $context['context']['error']);
        self::assertSame(200, $context['context']['status_code']);
        self::assertSame('2xx', $context['context']['status_family']);
        self::assertSame(200, $context['context']['expected_status_code']);
        self::assertIsInt($context['context']['duration_ms']);
    }

    public function testMiddlewareLogsClientErrorResponseAsWarning(): void
    {
        $observability = new InMemoryResponseObservability();
        $middleware = new ResponseLoggingMiddleware($observability, new HttpLogContextResolver());
        $request = (new Request('POST', '/account/acc_123/balance/withdraw'))
            ->withAttribute(CorrelationIdMiddleware::ATTRIBUTE, 'corr_123');

        $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStatus(422);
            }
        });

        self::assertCount(1, $observability->warningCalls);
        self::assertSame('http.response.rejected', $observability->warningCalls[0]['message']);
        self::assertSame(true, $observability->warningCalls[0]['context']->toArray()['context']['error']);
    }

    public function testMiddlewareLogsServerErrorResponseAsError(): void
    {
        $observability = new InMemoryResponseObservability();
        $middleware = new ResponseLoggingMiddleware($observability, new HttpLogContextResolver());
        $request = (new Request('GET', '/health'))
            ->withAttribute(CorrelationIdMiddleware::ATTRIBUTE, 'corr_500');

        $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withStatus(500);
            }
        });

        self::assertCount(1, $observability->errorCalls);
        self::assertSame('http.response.failed', $observability->errorCalls[0]['message']);
        self::assertSame('5xx', $observability->errorCalls[0]['context']->toArray()['context']['status_family']);
    }
}

final class InMemoryResponseObservability implements Observability
{
    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $infoCalls = [];

    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $warningCalls = [];

    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $errorCalls = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoCalls[] = ['message' => $message, 'context' => $context];
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->warningCalls[] = ['message' => $message, 'context' => $context];
    }

    public function error(string $message, LogContext $context): void
    {
        $this->errorCalls[] = ['message' => $message, 'context' => $context];
    }

    public function increment(MetricPoint $metricPoint): void
    {
    }
}
