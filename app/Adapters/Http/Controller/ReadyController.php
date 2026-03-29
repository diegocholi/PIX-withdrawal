<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Health\OperationalStatusPayloadFactory;
use Tecnofit\PixWithdrawal\Adapters\Http\Health\ReadinessProbe;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;

final readonly class ReadyController
{
    public function __construct(
        private OperationalStatusPayloadFactory $operationalStatusPayloadFactory,
        private ReadinessProbe $readinessProbe,
        private SuccessResponseFactory $successResponseFactory,
    ) {
    }

    public function show(): PsrResponseInterface
    {
        $isReady = $this->readinessProbe->isReady();

        return $this->successResponseFactory->create(
            $this->operationalStatusPayloadFactory->readiness($isReady),
            $isReady ? 200 : 503
        );
    }
}
