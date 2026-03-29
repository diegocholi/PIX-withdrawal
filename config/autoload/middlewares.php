<?php

declare(strict_types=1);

return [
    'http' => [
        \Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware::class,
        \Tecnofit\PixWithdrawal\Adapters\Http\Middleware\RequestLoggingMiddleware::class,
        \Tecnofit\PixWithdrawal\Adapters\Http\Middleware\ResponseLoggingMiddleware::class,
        \Tecnofit\PixWithdrawal\Plugins\Advanced\SecurityHeaders\SecurityHeadersMiddleware::class,
    ],
];
