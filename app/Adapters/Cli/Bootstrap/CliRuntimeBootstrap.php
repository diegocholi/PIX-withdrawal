<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap;

use Hyperf\Contract\ApplicationInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionContext;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliOptionName;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfRuntimeBootstrap;

final readonly class CliRuntimeBootstrap
{
    public function __construct(
        private HyperfRuntimeBootstrap $runtimeBootstrap = new HyperfRuntimeBootstrap(),
    ) {
    }

    public function bootContainer(): ContainerInterface
    {
        return $this->runtimeBootstrap->bootContainer();
    }

    public function bootApplication(): Application
    {
        $container = $this->bootContainer();
        $application = $container->get(ApplicationInterface::class);
        $this->configureGlobalOptions($application);
        $executionContext = $container->get(CliExecutionContext::class);
        $dispatcher = new EventDispatcher();
        $dispatcher->addSubscriber(new CliCommandLifecycleSubscriber(
            $container->get(Observability::class),
            $container->get(CliExecutionCorrelationProvider::class),
            $executionContext,
        ));
        $application->setDispatcher($dispatcher);

        return $application;
    }

    private function configureGlobalOptions(Application $application): void
    {
        $definition = $application->getDefinition();

        if ($definition->hasOption(CliOptionName::CORRELATION_ID)) {
            return;
        }

        $definition->addOption(new InputOption(
            CliOptionName::CORRELATION_ID,
            null,
            InputOption::VALUE_REQUIRED,
            'Correlation id explicito da execucao CLI. Quando ausente, o runtime gera um valor automaticamente.',
        ));
    }
}
