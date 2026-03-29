<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Response;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final readonly class SuccessResponseFactory
{
    public function __construct(
        private ResponseInterface $response,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     */
    public function create(array $payload, int $status = 200, array $headers = []): PsrResponseInterface
    {
        $response = $this->response
            ->json($payload)
            ->withStatus($status);

        $headers = $this->withCanonicalHeaders($payload, $headers);

        foreach ($headers as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }

    /**
     * @param array<string, mixed> $payload
     * @param array<string, string> $headers
     * @return array<string, string>
     */
    private function withCanonicalHeaders(array $payload, array $headers): array
    {
        $correlationId = $payload['meta']['correlation_id'] ?? null;

        if (is_string($correlationId) && trim($correlationId) !== '') {
            $headers[ApiHeader::CORRELATION_ID] ??= trim($correlationId);
        }

        return $headers;
    }
}
