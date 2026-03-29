<?php

declare(strict_types=1);

use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\Handler\UnexpectedThrowableHandler;

return [
    'handler' => [
        'http' => [
            HttpExceptionHandler::class,
            UnexpectedThrowableHandler::class,
        ],
    ],
];
