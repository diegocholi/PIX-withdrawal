<?php

declare(strict_types=1);

use function Hyperf\Support\env;

$applicationEnvironment = trim((string) env('APP_ENV', 'prod'));
$defaultHost = $applicationEnvironment === 'local' ? 'mysql' : '127.0.0.1';
$defaultPort = '3306';
$defaultDatabase = 'pix_withdrawal';
$defaultUsername = 'pix_withdrawal';
$defaultPassword = 'pix_withdrawal';
$defaultConnection = [
    'driver' => 'mysql',
    'host' => (string) env('DB_HOST', $defaultHost),
    'port' => (int) env('DB_PORT', $defaultPort),
    'database' => (string) env('DB_DATABASE', $defaultDatabase),
    'username' => (string) env('DB_USERNAME', $defaultUsername),
    'password' => (string) env('DB_PASSWORD', $defaultPassword),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'strict' => true,
];

return [
    'default' => $defaultConnection,
    'default_connection' => 'default',
    'connections' => [
        'default' => $defaultConnection,
    ],
];
