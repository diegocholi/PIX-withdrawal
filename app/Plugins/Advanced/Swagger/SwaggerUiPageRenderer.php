<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Advanced\Swagger;

use Tecnofit\PixWithdrawal\Plugins\Advanced\Config\AdvancedPluginConfig;

final readonly class SwaggerUiPageRenderer
{
    public function __construct(
        private AdvancedPluginConfig $config,
    ) {
    }

    public function render(): string
    {
        $title = htmlspecialchars($this->config->openApiTitle(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $specUrl = htmlspecialchars($this->config->specPath(), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$title} - Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css">
    <style>
        html { box-sizing: border-box; overflow-y: scroll; }
        *, *::before, *::after { box-sizing: inherit; }
        body { margin: 0; background: #f5f7fb; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: '{$specUrl}',
                dom_id: '#swagger-ui',
                deepLinking: true,
                displayRequestDuration: true,
                persistAuthorization: false
            });
        };
    </script>
</body>
</html>
HTML;
    }
}
