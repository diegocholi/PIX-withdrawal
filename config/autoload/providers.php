<?php

declare(strict_types=1);

use Tecnofit\PixWithdrawal\Providers\Bootstrap\ProviderRuntimeBootstrap;

use function Hyperf\Support\env;

return (new ProviderRuntimeBootstrap())->bootstrap(
    (string) env('APP_ENV', 'prod'),
    (int) env('WITHDRAW_DUPLICATE_GUARD_WINDOW_SECONDS', 60),
);
