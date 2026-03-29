<?php

declare(strict_types=1);

use function Hyperf\Support\env;

$applicationEnvironment = (string) env('APP_ENV', 'prod');
$debugMode = filter_var(
    env('APP_DEBUG', $applicationEnvironment === 'dev' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOL
);

return [
    'name' => (string) env('APP_NAME', 'pix-withdrawal'),
    'env' => $applicationEnvironment,
    'debug' => $debugMode,
    'locale' => (string) env('APP_LOCALE', 'pt_BR'),
    'fallback_locale' => (string) env('APP_FALLBACK_LOCALE', 'en'),
    'charset' => (string) env('APP_CHARSET', 'UTF-8'),
    'timezone' => (string) env('APP_TIMEZONE', 'UTC'),
];
