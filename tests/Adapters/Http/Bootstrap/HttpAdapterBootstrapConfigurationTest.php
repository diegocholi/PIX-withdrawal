<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Http\Bootstrap;

use FastRoute\Dispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Hyperf\HttpServer\Router\DispatcherFactory;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\OpenApiController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\ReadyController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\SwaggerUiController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawController;
use Tecnofit\PixWithdrawal\Adapters\Http\Controller\WithdrawStatusController;
use Tecnofit\PixWithdrawal\Adapters\Http\Exception\Handler\UnexpectedThrowableHandler;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\CorrelationIdMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\RequestLoggingMiddleware;
use Tecnofit\PixWithdrawal\Adapters\Http\Middleware\ResponseLoggingMiddleware;
use Tecnofit\PixWithdrawal\Plugins\Advanced\SecurityHeaders\SecurityHeadersMiddleware;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class HttpAdapterBootstrapConfigurationTest extends TestCase
{
    public function testHttpAdapterRegistersBootstrapConfiguration(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        self::assertSame(
            [
                CorrelationIdMiddleware::class,
                RequestLoggingMiddleware::class,
                ResponseLoggingMiddleware::class,
                SecurityHeadersMiddleware::class,
            ],
            $config->get('middlewares.http')
        );
        self::assertSame(
            [
                HttpExceptionHandler::class,
                UnexpectedThrowableHandler::class,
            ],
            $config->get('exceptions.handler.http')
        );
    }

    public function testHttpAdapterRegistersCreateWithdrawPostRoute(): void
    {
        $dispatcher = (new DispatcherFactory())->getDispatcher('http');
        $routeMatch = $dispatcher->dispatch('POST', '/account/acc_123/balance/withdraw');

        self::assertSame(Dispatcher::FOUND, $routeMatch[0]);
        self::assertSame([WithdrawController::class, 'create'], $routeMatch[1]->callback);
        self::assertSame('/account/{accountId}/balance/withdraw', $routeMatch[1]->route);
        self::assertSame(['accountId' => 'acc_123'], $routeMatch[2]);
    }

    public function testHttpAdapterRegistersFindWithdrawStatusGetRoute(): void
    {
        $dispatcher = (new DispatcherFactory())->getDispatcher('http');
        $routeMatch = $dispatcher->dispatch('GET', '/account/acc_123/balance/withdraw/wd_123');

        self::assertSame(Dispatcher::FOUND, $routeMatch[0]);
        self::assertSame([WithdrawStatusController::class, 'show'], $routeMatch[1]->callback);
        self::assertSame('/account/{accountId}/balance/withdraw/{withdrawId}', $routeMatch[1]->route);
        self::assertSame(['accountId' => 'acc_123', 'withdrawId' => 'wd_123'], $routeMatch[2]);
    }

    public function testHttpAdapterRegistersReadyGetRoute(): void
    {
        $dispatcher = (new DispatcherFactory())->getDispatcher('http');
        $routeMatch = $dispatcher->dispatch('GET', '/ready');

        self::assertSame(Dispatcher::FOUND, $routeMatch[0]);
        self::assertSame([ReadyController::class, 'show'], $routeMatch[1]->callback);
        self::assertSame('/ready', $routeMatch[1]->route);
        self::assertSame([], $routeMatch[2]);
    }

    public function testHttpAdapterRegistersOpenApiGetRoute(): void
    {
        $dispatcher = (new DispatcherFactory())->getDispatcher('http');
        $routeMatch = $dispatcher->dispatch('GET', '/openapi.json');

        self::assertSame(Dispatcher::FOUND, $routeMatch[0]);
        self::assertSame([OpenApiController::class, 'show'], $routeMatch[1]->callback);
        self::assertSame('/openapi.json', $routeMatch[1]->route);
        self::assertSame([], $routeMatch[2]);
    }

    public function testHttpAdapterRegistersSwaggerUiGetRoute(): void
    {
        $dispatcher = (new DispatcherFactory())->getDispatcher('http');
        $routeMatch = $dispatcher->dispatch('GET', '/docs');

        self::assertSame(Dispatcher::FOUND, $routeMatch[0]);
        self::assertSame([SwaggerUiController::class, 'show'], $routeMatch[1]->callback);
        self::assertSame('/docs', $routeMatch[1]->route);
        self::assertSame([], $routeMatch[2]);
    }
}
