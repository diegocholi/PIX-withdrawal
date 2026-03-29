<?php

declare(strict_types=1);

use Hyperf\Framework\Bootstrap\PipeMessageCallback;
use Hyperf\Framework\Bootstrap\ServerStartCallback;
use Hyperf\Framework\Bootstrap\WorkerExitCallback;
use Hyperf\Framework\Bootstrap\WorkerStartCallback;
use Hyperf\Server\Event;
use Hyperf\Server\Server;
use Hyperf\Server\ServerInterface;

use function Hyperf\Support\env;

return [
    'type' => Server::class,
    'mode' => SWOOLE_BASE,
    'servers' => [
        [
            'name' => 'http',
            'type' => ServerInterface::SERVER_HTTP,
            'host' => (string) env('HTTP_HOST', '0.0.0.0'),
            'port' => (int) env('HTTP_PORT', 9501),
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
            'options' => [
                'enable_request_lifecycle' => false,
            ],
        ],
    ],
    'processes' => [],
    'settings' => [
        'enable_coroutine' => true,
        'worker_num' => (int) env('HTTP_WORKER_NUM', 4),
        'pid_file' => BASE_PATH . '/runtime/hyperf.pid',
        'open_tcp_nodelay' => true,
        'max_coroutine' => 100000,
        'open_http2_protocol' => true,
        'max_request' => 0,
        'socket_buffer_size' => 2 * 1024 * 1024,
    ],
    'callbacks' => [
        Event::ON_BEFORE_START => [ServerStartCallback::class, 'beforeStart'],
        Event::ON_WORKER_START => [WorkerStartCallback::class, 'onWorkerStart'],
        Event::ON_PIPE_MESSAGE => [PipeMessageCallback::class, 'onPipeMessage'],
        Event::ON_WORKER_EXIT => [WorkerExitCallback::class, 'onWorkerExit'],
    ],
];
