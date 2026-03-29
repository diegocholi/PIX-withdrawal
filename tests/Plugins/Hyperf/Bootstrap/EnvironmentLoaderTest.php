<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Hyperf\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\EnvironmentLoader;

final class EnvironmentLoaderTest extends TestCase
{
    public function testLoadReadsEnvironmentVariablesFromDotEnvFile(): void
    {
        $projectBasePath = dirname(__DIR__, 4);
        $temporaryDirectory = $projectBasePath . '/runtime/tmp/environment-loader-test';

        if (! is_dir($temporaryDirectory)) {
            mkdir($temporaryDirectory, 0777, true);
        }

        file_put_contents(
            $temporaryDirectory . '/.env',
            "BOOTSTRAP_ENVIRONMENT_TOKEN=loaded-from-dotenv\n"
        );

        (new EnvironmentLoader($temporaryDirectory))->load(true);

        self::assertSame('loaded-from-dotenv', getenv('BOOTSTRAP_ENVIRONMENT_TOKEN'));

        unlink($temporaryDirectory . '/.env');
        rmdir($temporaryDirectory);
    }
}
