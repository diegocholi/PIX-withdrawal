<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\Contract\ConfigInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Adapters\Http\Response\SuccessResponseFactory;

final readonly class BootstrapController
{
    public function __construct(
        private ConfigInterface $config,
        private SuccessResponseFactory $successResponseFactory,
    ) {
    }

    public function index(): PsrResponseInterface
    {
        return $this->successResponseFactory->create([
            'name' => (string) $this->config->get('app.name', 'pix-withdrawal'),
            'status' => 'ok',
            'runtime' => 'http',
        ]);
    }
}
