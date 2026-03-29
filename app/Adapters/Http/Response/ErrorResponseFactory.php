<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Response;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final readonly class ErrorResponseFactory
{
    public function __construct(
        private ResponseInterface $response,
    ) {
    }

    /**
     * @param array<string, mixed> $details
     */
    public function create(
        string $code,
        string $message,
        int $status,
        array $details = [],
        ?string $correlationId = null,
    ): PsrResponseInterface {
        $payload = [
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => [
                'correlation_id' => $correlationId,
            ],
        ];

        $response = $this->response
            ->json($payload)
            ->withStatus($status);

        if (is_string($correlationId) && trim($correlationId) !== '') {
            $response = $response->withHeader(ApiHeader::CORRELATION_ID, trim($correlationId));
        }

        return $response;
    }
}
