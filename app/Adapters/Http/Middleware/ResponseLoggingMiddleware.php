<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final readonly class ResponseLoggingMiddleware implements MiddlewareInterface
{
    public function __construct(
        private Observability $observability,
        private HttpLogContextResolver $httpLogContextResolver,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $startedAt = microtime(true);
        $response = $handler->handle($request);
        $method = strtoupper(trim($request->getMethod()));
        $path = $request->getUri()->getPath();
        $resourceIdentifiers = $this->httpLogContextResolver->resourceIdentifiers($path);
        $statusCode = $response->getStatusCode();
        $level = $this->resolveLevel($statusCode);
        $message = $this->resolveMessage($statusCode);

        $this->observability->{$level}(
            $message,
            new LogContext(
                correlationId: $this->httpLogContextResolver->correlationId($request),
                withdrawId: $resourceIdentifiers['withdraw_id'] ?? null,
                accountId: $resourceIdentifiers['account_id'] ?? null,
                status: (string) $statusCode,
                traceMetadata: [
                    'transport' => 'http',
                    'direction' => 'outbound',
                ],
                context: $this->removeNullEntries([
                    'method' => $method,
                    'path' => $path,
                    'status_code' => $statusCode,
                    'duration_ms' => $this->durationInMilliseconds($startedAt),
                    'error' => $statusCode >= 400,
                    'status_family' => $this->statusFamily($statusCode),
                    'expected_status_code' => $this->httpLogContextResolver->expectedStatusCode($method, $path),
                ])
            )
        );

        return $response;
    }

    private function resolveLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        }

        if ($statusCode >= 400) {
            return 'warning';
        }

        return 'info';
    }

    private function resolveMessage(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'http.response.failed';
        }

        if ($statusCode >= 400) {
            return 'http.response.rejected';
        }

        return 'http.response.sent';
    }

    private function durationInMilliseconds(float $startedAt): int
    {
        return max(0, (int) round((microtime(true) - $startedAt) * 1000));
    }

    private function statusFamily(int $statusCode): string
    {
        return sprintf('%dxx', (int) floor($statusCode / 100));
    }

    /**
     * @param array<string, scalar|bool|null> $values
     * @return array<string, scalar|bool>
     */
    private function removeNullEntries(array $values): array
    {
        return array_filter(
            $values,
            static fn (mixed $value): bool => $value !== null
        );
    }
}
