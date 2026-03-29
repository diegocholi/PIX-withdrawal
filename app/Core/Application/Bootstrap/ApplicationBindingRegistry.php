<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Core\Application\Bootstrap;

use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdraw;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\CreateWithdrawHandler;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatus;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\FindWithdrawStatusHandler;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdraw;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdrawHandler;

final class ApplicationBindingRegistry
{
    /**
     * @return array<class-string, class-string>
     */
    public static function definitions(): array
    {
        return [
            CreateWithdraw::class => CreateWithdrawHandler::class,
            FindWithdrawStatus::class => FindWithdrawStatusHandler::class,
            ProcessWithdraw::class => ProcessWithdrawHandler::class,
        ];
    }
}
