<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap;

use Hyperf\Context\ApplicationContext;
use Hyperf\Di\ClassLoader;
use Hyperf\Di\Container;
use Hyperf\Di\Definition\DefinitionSourceFactory;
use Psr\Container\ContainerInterface;

final class HyperfContainerFactory
{
    public function create(): ContainerInterface
    {
        $basePath = $this->defineBasePath();

        (new EnvironmentLoader($basePath))->load();
        (new BootstrapRuntimeConfigurator())->configure();

        ClassLoader::init();

        return ApplicationContext::setContainer(
            new Container((new DefinitionSourceFactory())())
        );
    }

    private function defineBasePath(): string
    {
        if (defined('BASE_PATH')) {
            return BASE_PATH;
        }

        define('BASE_PATH', dirname(__DIR__, 4));

        return BASE_PATH;
    }
}
