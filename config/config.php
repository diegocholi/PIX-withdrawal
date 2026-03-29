<?php

declare(strict_types=1);

use Hyperf\Contract\StdoutLoggerInterface;
use Psr\Log\LogLevel;

use function Hyperf\Support\env;

$applicationEnvironment = (string) env('APP_ENV', 'prod');
$debugMode = filter_var(
    env('APP_DEBUG', $applicationEnvironment === 'dev' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOL
);

$stdoutLogLevels = [
    LogLevel::EMERGENCY,
    LogLevel::ALERT,
    LogLevel::CRITICAL,
    LogLevel::ERROR,
    LogLevel::WARNING,
    LogLevel::NOTICE,
    LogLevel::INFO,
];

if ($debugMode) {
    $stdoutLogLevels[] = LogLevel::DEBUG;
}

return [
    'scan_cacheable' => false,
    StdoutLoggerInterface::class => [
        'log_level' => $stdoutLogLevels,
    ],
];
