<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

final readonly class WithdrawAcceptedResponse
{
    public function __construct(
        private SuccessResponseFactory $successResponseFactory,
    ) {
    }

    /**
     * @param array{
     *     data: array{status_url: string},
     *     meta: array{correlation_id: string}
     * } $payload
     */
    public function create(array $payload): PsrResponseInterface
    {
        return $this->successResponseFactory->create(
            payload: $payload,
            status: 202,
            headers: [
                ApiHeader::LOCATION => $payload['data']['status_url'],
            ],
        );
    }
}
