<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Tecnofit\PixWithdrawal\Adapters\Http\Health\OperationalStatusPayloadFactory;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;

final readonly class HealthController
{
    public function __construct(
        private OperationalStatusPayloadFactory $operationalStatusPayloadFactory,
        private SuccessResponseFactory $successResponseFactory,
    ) {
    }

    public function show(): PsrResponseInterface
    {
        return $this->successResponseFactory->create(
            $this->operationalStatusPayloadFactory->health()
        );
    }
}
