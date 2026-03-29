<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Plugins\Identifier\RandomUuidGenerator;

final class RandomUuidGeneratorFactory
{
    public function create(): RandomUuidGenerator
    {
        return new RandomUuidGenerator();
    }
}
