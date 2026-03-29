<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Http\Route;

use Hyperf\HttpServer\Router\Router;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\BootstrapController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\HealthController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\OpenApiController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\ReadyController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\SwaggerUiController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawStatusController;

use function Hyperf\Support\env;

final class HttpRouteRegistrar
{
    public static function register(): void
    {
        Router::get('/', [BootstrapController::class, 'index']);
        Router::get('/health', [HealthController::class, 'show']);
        Router::get('/ready', [ReadyController::class, 'show']);
        Router::get('/account/{accountId}/balance/withdraw/{withdrawId}', [WithdrawStatusController::class, 'show']);
        Router::post('/account/{accountId}/balance/withdraw', [WithdrawController::class, 'create']);
        Router::get(self::openApiPath(), [OpenApiController::class, 'show']);
        Router::get(self::swaggerUiPath(), [SwaggerUiController::class, 'show']);
    }

    private static function openApiPath(): string
    {
        return self::normalizePath((string) env('ADVANCED_OPENAPI_SPEC_PATH', '/openapi.json'));
    }

    private static function swaggerUiPath(): string
    {
        return self::normalizePath((string) env('ADVANCED_SWAGGER_UI_PATH', '/docs'));
    }

    private static function normalizePath(string $path): string
    {
        $normalizedPath = trim($path);

        if ($normalizedPath === '' || $normalizedPath === '/') {
            return '/';
        }

        return '/' . trim($normalizedPath, '/');
    }
}
