<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

interface KafkaProducerTransport
{
    public function publish(KafkaProducerRecord $record): void;
}
