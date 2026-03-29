<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\SecurityHeaders;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private SecurityHeadersPolicy $securityHeadersPolicy,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->securityHeadersPolicy->headers() as $header => $value) {
            if (! $response->hasHeader($header)) {
                $response = $response->withHeader($header, $value);
            }
        }

        return $response;
    }
}
