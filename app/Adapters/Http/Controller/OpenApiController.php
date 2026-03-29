<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger\OpenApiDocumentFactory;

final readonly class OpenApiController
{
    public function __construct(
        private SuccessResponseFactory $successResponseFactory,
        private OpenApiDocumentFactory $openApiDocumentFactory,
    ) {
    }

    public function show(): PsrResponseInterface
    {
        return $this->successResponseFactory->create($this->openApiDocumentFactory->create()->toArray());
    }
}
