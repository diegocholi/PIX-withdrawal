<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Controller;

use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ResponseInterface as PsrResponseInterface;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;
use Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger\SwaggerUiPageRenderer;

final readonly class SwaggerUiController
{
    public function __construct(
        private ResponseInterface $response,
        private AdvancedPluginConfig $config,
        private SwaggerUiPageRenderer $swaggerUiPageRenderer,
    ) {
    }

    public function show(): PsrResponseInterface
    {
        if (! $this->config->swaggerUiEnabled()) {
            return $this->response->raw('Not Found')->withStatus(404);
        }

        return $this->response
            ->raw($this->swaggerUiPageRenderer->render())
            ->withHeader('content-type', 'text/html; charset=utf-8');
    }
}
