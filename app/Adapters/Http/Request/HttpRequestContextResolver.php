<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Request;

use Hyperf\HttpServer\Contract\RequestInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\ApiHeader;

final readonly class HttpRequestContextResolver
{
    public function __construct(private RequestInterface $request)
    {
    }

    public function correlationId(): ?string
    {
        $attribute = $this->request->getAttribute(CorrelationIdMiddleware::ATTRIBUTE);

        if (is_string($attribute) && trim($attribute) !== '') {
            return trim($attribute);
        }

        $header = $this->request->header(ApiHeader::CORRELATION_ID);

        if (! is_string($header)) {
            return null;
        }

        $correlationId = trim($header);

        return $correlationId === '' ? null : $correlationId;
    }

    public function routePath(): string
    {
        $path = trim($this->request->getPathInfo());

        if ($path !== '') {
            return $path;
        }

        $uri = $this->request->getUri();
        $path = trim($uri->getPath());

        return $path === '' ? '/' : $path;
    }
}
