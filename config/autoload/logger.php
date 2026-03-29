<?php

declare(strict_types=1);

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;

use function Hyperf\Support\env;

$applicationEnvironment = (string) env('APP_ENV', 'prod');
$debugMode = filter_var(
    env('APP_DEBUG', $applicationEnvironment === 'dev' ? 'true' : 'false'),
    FILTER_VALIDATE_BOOL
);
$defaultLogLevel = $applicationEnvironment === 'dev' ? 'DEBUG' : 'INFO';
$applicationLogFile = BASE_PATH . '/runtime/logs/application/application.log';
$applicationLogMaxFiles = (int) env('APP_LOG_MAX_FILES', '14');
$defaultLineFormat = '[%datetime%] %channel%.%level_name%: %message% %context% %extra%' . PHP_EOL;

return [
    'default' => [
        'handlers' => [
            [
                'class' => RotatingFileHandler::class,
                'constructor' => [
                    'filename' => $applicationLogFile,
                    'maxFiles' => $applicationLogMaxFiles,
                    'level' => Level::fromName((string) env('APP_LOG_LEVEL', $defaultLogLevel)),
                ],
                'formatter' => [
                    'class' => LineFormatter::class,
                    'constructor' => [
                        'format' => $defaultLineFormat,
                        'dateFormat' => 'Y-m-d\\TH:i:sP',
                        'allowInlineLineBreaks' => false,
                        'ignoreEmptyContextAndExtra' => true,
                        'includeStacktraces' => $debugMode,
                    ],
                ],
            ],
        ],
        'processors' => [],
    ],
];
