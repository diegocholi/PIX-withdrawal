<?php

declare(strict_types=1);

use function Hyperf\Support\env;

$applicationEnvironment = (string) env('APP_ENV', 'prod');
$swaggerUiEnabled = filter_var(
    env('ADVANCED_SWAGGER_UI_ENABLED', $applicationEnvironment !== 'prod' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOL
);

return [
    'openapi' => [
        'title' => (string) env('ADVANCED_OPENAPI_TITLE', 'PIX Withdrawal API'),
        'version' => (string) env('ADVANCED_OPENAPI_VERSION', '1.0.0'),
        'spec_path' => (string) env('ADVANCED_OPENAPI_SPEC_PATH', '/openapi.json'),
    ],
    'swagger_ui' => [
        'path' => (string) env('ADVANCED_SWAGGER_UI_PATH', '/docs'),
        'enabled' => $swaggerUiEnabled,
    ],
];
