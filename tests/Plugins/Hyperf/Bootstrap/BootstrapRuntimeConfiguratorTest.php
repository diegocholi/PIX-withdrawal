<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Hyperf\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\BootstrapRuntimeConfigurator;

final class BootstrapRuntimeConfiguratorTest extends TestCase
{
    private string $originalTimezone;

    private string|false $originalDisplayErrors;

    private string|false $originalDisplayStartupErrors;

    private string|false $originalHtmlErrors;

    private string|false $originalLogErrors;

    private string|false $originalErrorLog;

    protected function setUp(): void
    {
        parent::setUp();

        $this->originalTimezone = date_default_timezone_get();
        $this->originalDisplayErrors = ini_get('display_errors');
        $this->originalDisplayStartupErrors = ini_get('display_startup_errors');
        $this->originalHtmlErrors = ini_get('html_errors');
        $this->originalLogErrors = ini_get('log_errors');
        $this->originalErrorLog = ini_get('error_log');
    }

    protected function tearDown(): void
    {
        date_default_timezone_set($this->originalTimezone);
        ini_set('display_errors', $this->normalizeIniValue($this->originalDisplayErrors));
        ini_set('display_startup_errors', $this->normalizeIniValue($this->originalDisplayStartupErrors));
        ini_set('html_errors', $this->normalizeIniValue($this->originalHtmlErrors));
        ini_set('log_errors', $this->normalizeIniValue($this->originalLogErrors));
        ini_set('error_log', $this->normalizeIniValue($this->originalErrorLog));

        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testConfigureAppliesSafeDefaultsWhenEnvironmentVariablesAreMissing(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_TIMEZONE');

        (new BootstrapRuntimeConfigurator())->configure();

        self::assertSame('UTC', date_default_timezone_get());
        self::assertSame('UTF-8', ini_get('default_charset'));
        self::assertSame('0', ini_get('display_errors'));
        self::assertSame('0', ini_get('display_startup_errors'));
        self::assertSame('0', ini_get('html_errors'));
        self::assertSame('1', ini_get('log_errors'));
        self::assertSame($this->expectedBasePath() . '/runtime/logs/php/php-error.log', ini_get('error_log'));
    }

    public function testConfigureHonorsDevelopmentOverrides(): void
    {
        putenv('APP_ENV=dev');
        putenv('APP_DEBUG=true');
        putenv('APP_CHARSET=ISO-8859-1');
        putenv('APP_TIMEZONE=America/Sao_Paulo');

        (new BootstrapRuntimeConfigurator())->configure();

        self::assertSame('America/Sao_Paulo', date_default_timezone_get());
        self::assertSame('ISO-8859-1', ini_get('default_charset'));
        self::assertSame('stderr', ini_get('display_errors'));
        self::assertSame('1', ini_get('display_startup_errors'));
        self::assertSame('0', ini_get('html_errors'));
        self::assertSame('1', ini_get('log_errors'));
        self::assertSame($this->expectedBasePath() . '/runtime/logs/php/php-error.log', ini_get('error_log'));
    }

    private function normalizeIniValue(string|false $value): string
    {
        if ($value === false) {
            return '';
        }

        return $value;
    }

    private function expectedBasePath(): string
    {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }

        return dirname(__DIR__, 4);
    }
}
