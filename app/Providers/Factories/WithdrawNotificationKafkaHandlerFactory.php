<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Factories;

use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaMessageMapper;

final readonly class WithdrawNotificationKafkaHandlerFactory
{
    public function __construct(private WithdrawNotificationDispatcher $withdrawNotificationDispatcher)
    {
    }

    public function create(): WithdrawNotificationKafkaHandler
    {
        return new WithdrawNotificationKafkaHandler(
            new WithdrawNotificationKafkaMessageMapper(),
            $this->withdrawNotificationDispatcher,
        );
    }
}
