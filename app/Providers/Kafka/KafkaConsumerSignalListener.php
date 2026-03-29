<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

interface KafkaConsumerSignalListener
{
    /**
     * @param \Closure(): void $stopConsumer
     */
    public function listen(\Closure $stopConsumer): void;
}
