<?php

declare(strict_types=1);

use Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap\CliRuntimeBootstrap;

require_once __DIR__ . '/../vendor/autoload.php';

$application = (new CliRuntimeBootstrap())->bootApplication();

exit($application->run());
