<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class ProviderRuntimeResolutionTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_LOG_LEVEL');
        putenv('APP_LOCALE');
        putenv('APP_FALLBACK_LOCALE');
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testConfiguredProviderBindingsResolveForEverySupportedRuntime(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        /** @var array<string, list<class-string>> $requiredBindings */
        $requiredBindings = $config->get('providers.runtime.required_bindings', []);

        self::assertNotEmpty($requiredBindings);

        foreach ($requiredBindings as $runtime => $bindings) {
            self::assertNotEmpty($bindings, sprintf('Runtime "%s" must declare at least one binding.', $runtime));

            foreach ($bindings as $binding) {
                self::assertTrue(
                    $container->has($binding),
                    sprintf('Runtime "%s" must expose binding "%s".', $runtime, $binding)
                );
                self::assertNotNull(
                    $container->get($binding),
                    sprintf('Runtime "%s" must resolve binding "%s".', $runtime, $binding)
                );
            }
        }
    }

    public function testConfiguredProviderBindingsAlsoResolveInTestEnvironment(): void
    {
        putenv('APP_ENV=test');

        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        /** @var array<string, list<class-string>> $requiredBindings */
        $requiredBindings = $config->get('providers.runtime.required_bindings', []);

        foreach ($requiredBindings as $bindings) {
            foreach ($bindings as $binding) {
                self::assertNotNull($container->get($binding));
            }
        }
    }
}
