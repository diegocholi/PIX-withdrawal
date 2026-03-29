<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Mail;

use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerHandler;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final readonly class WithdrawNotificationKafkaHandler implements KafkaConsumerHandler
{
    public function __construct(
        private WithdrawNotificationKafkaMessageMapper $withdrawNotificationKafkaMessageMapper,
        private WithdrawNotificationDispatcher $withdrawNotificationDispatcher,
    ) {
    }

    public function handle(KafkaConsumerMessage $message): void
    {
        $this->withdrawNotificationDispatcher->dispatchCommand(
            $this->withdrawNotificationKafkaMessageMapper->map($message)
        );
    }
}
