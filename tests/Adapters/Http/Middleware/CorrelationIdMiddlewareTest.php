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
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;

final class CorrelationIdMiddlewareTest extends TestCase
{
    public function testMiddlewarePropagatesIncomingCorrelationIdToRequestAttributeAndResponseHeader(): void
    {
        $middleware = new CorrelationIdMiddleware(new FixedMiddlewareCorrelationIdGenerator('generated-corr'));
        $request = (new Request('GET', '/health'))->withHeader(ApiHeader::CORRELATION_ID, 'incoming-corr');
        $capturedRequest = null;

        $response = $middleware->process($request, new class ($capturedRequest) implements RequestHandlerInterface {
            public ?ServerRequestInterface $capturedRequest = null;

            public function __construct(?ServerRequestInterface &$capturedRequest)
            {
                $this->capturedRequest = $capturedRequest;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->capturedRequest = $request;

                return new Response();
            }
        });

        self::assertSame('incoming-corr', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
    }

    public function testMiddlewareGeneratesCorrelationIdWhenHeaderIsMissing(): void
    {
        $middleware = new CorrelationIdMiddleware(new FixedMiddlewareCorrelationIdGenerator('generated-corr'));
        $request = new Request('GET', '/health');
        $capturedCorrelationId = null;

        $response = $middleware->process($request, new class ($capturedCorrelationId) implements RequestHandlerInterface {
            public ?string $capturedCorrelationId = null;

            public function __construct(?string &$capturedCorrelationId)
            {
                $this->capturedCorrelationId = $capturedCorrelationId;
            }

            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->capturedCorrelationId = $request->getAttribute(CorrelationIdMiddleware::ATTRIBUTE);

                return new Response();
            }
        });

        self::assertSame('generated-corr', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
    }

    public function testMiddlewarePreservesExistingCorrelationIdResponseHeader(): void
    {
        $middleware = new CorrelationIdMiddleware(new FixedMiddlewareCorrelationIdGenerator('generated-corr'));
        $request = new Request('GET', '/health');

        $response = $middleware->process($request, new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return (new Response())->withHeader(ApiHeader::CORRELATION_ID, 'handler-corr');
            }
        });

        self::assertSame('handler-corr', $response->getHeaderLine(ApiHeader::CORRELATION_ID));
    }
}

final readonly class FixedMiddlewareCorrelationIdGenerator implements CorrelationIdGenerator
{
    public function __construct(private string $value)
    {
    }

    public function generate(): string
    {
        return $this->value;
    }
}
