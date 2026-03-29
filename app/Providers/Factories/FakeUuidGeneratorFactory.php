<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Plugins\Identifier\FakeUuidGenerator;

final class FakeUuidGeneratorFactory
{
    public function create(): FakeUuidGenerator
    {
        return new FakeUuidGenerator();
    }
}
