<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

interface KafkaConsumerHandler
{
    public function handle(KafkaConsumerMessage $message): void;
}
