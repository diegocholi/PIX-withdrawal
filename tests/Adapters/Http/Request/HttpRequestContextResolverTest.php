<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Request;

use Hyperf\HttpServer\Contract\RequestInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\UriInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Request\HttpRequestContextResolver;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;

final class HttpRequestContextResolverTest extends TestCase
{
    public function testCorrelationIdPrefersMiddlewareAttribute(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getAttribute')->with(CorrelationIdMiddleware::ATTRIBUTE)->willReturn(' corr-attr ');
        $request->expects(self::never())->method('header');

        $resolver = new HttpRequestContextResolver($request);

        self::assertSame('corr-attr', $resolver->correlationId());
    }

    public function testCorrelationIdFallsBackToHeader(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getAttribute')->with(CorrelationIdMiddleware::ATTRIBUTE)->willReturn(null);
        $request->method('header')->with(ApiHeader::CORRELATION_ID)->willReturn(' corr-header ');

        $resolver = new HttpRequestContextResolver($request);

        self::assertSame('corr-header', $resolver->correlationId());
    }

    public function testCorrelationIdReturnsNullWhenUnavailable(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getAttribute')->with(CorrelationIdMiddleware::ATTRIBUTE)->willReturn('');
        $request->method('header')->with(ApiHeader::CORRELATION_ID)->willReturn(null);

        $resolver = new HttpRequestContextResolver($request);

        self::assertNull($resolver->correlationId());
    }

    public function testRoutePathPrefersPathInfo(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $request->method('getPathInfo')->willReturn('/account/id');
        $request->expects(self::never())->method('getUri');

        $resolver = new HttpRequestContextResolver($request);

        self::assertSame('/account/id', $resolver->routePath());
    }

    public function testRoutePathFallsBackToUriPath(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $request->method('getPathInfo')->willReturn(' ');
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn('/ready');

        $resolver = new HttpRequestContextResolver($request);

        self::assertSame('/ready', $resolver->routePath());
    }

    public function testRoutePathDefaultsToRootWhenPathIsEmpty(): void
    {
        $request = $this->createMock(RequestInterface::class);
        $uri = $this->createMock(UriInterface::class);
        $request->method('getPathInfo')->willReturn('');
        $request->method('getUri')->willReturn($uri);
        $uri->method('getPath')->willReturn(' ');

        $resolver = new HttpRequestContextResolver($request);

        self::assertSame('/', $resolver->routePath());
    }
}
