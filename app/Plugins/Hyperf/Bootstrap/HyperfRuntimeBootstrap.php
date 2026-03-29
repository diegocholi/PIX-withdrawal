<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Application;

final readonly class HyperfRuntimeBootstrap
{
    public function __construct(private HyperfContainerFactory $containerFactory = new HyperfContainerFactory())
    {
    }

    public function bootContainer(): ContainerInterface
    {
        return $this->containerFactory->create();
    }

    public function bootApplication(): Application
    {
        return $this->bootContainer()->get(\Hyperf\Contract\ApplicationInterface::class);
    }
}
