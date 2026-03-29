<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap;

use InvalidArgumentException;

use function Hyperf\Support\env;

final class BootstrapRuntimeConfigurator
{
    public function configure(): void
    {
        $environment = $this->environment();
        $debugMode = $this->debugMode($environment);
        $charset = $this->charset();
        $errorLogFile = $this->errorLogFile();
        $timezone = $this->timezone();

        if (date_default_timezone_set($timezone) === false) {
            throw new InvalidArgumentException(sprintf('Unsupported APP_TIMEZONE value: %s', $timezone));
        }

        if (ini_set('default_charset', $charset) === false) {
            throw new InvalidArgumentException(sprintf('Unsupported APP_CHARSET value: %s', $charset));
        }

        if (ini_set('error_log', $errorLogFile) === false) {
            throw new InvalidArgumentException(sprintf('Unable to configure PHP error_log destination: %s', $errorLogFile));
        }

        ini_set('display_errors', $debugMode ? 'stderr' : '0');
        ini_set('display_startup_errors', $debugMode ? '1' : '0');
        ini_set('html_errors', '0');
        ini_set('log_errors', '1');

        error_reporting(E_ALL);
    }

    private function environment(): string
    {
        return (string) env('APP_ENV', 'prod');
    }

    private function debugMode(string $environment): bool
    {
        $defaultDebugMode = $environment === 'dev' ? 'true' : 'false';

        return filter_var(env('APP_DEBUG', $defaultDebugMode), FILTER_VALIDATE_BOOL);
    }

    private function timezone(): string
    {
        return (string) env('APP_TIMEZONE', 'UTC');
    }

    private function charset(): string
    {
        return (string) env('APP_CHARSET', 'UTF-8');
    }

    private function errorLogFile(): string
    {
        return $this->basePath() . '/runtime/logs/php/php-error.log';
    }

    private function basePath(): string
    {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }

        return dirname(__DIR__, 4);
    }
}
