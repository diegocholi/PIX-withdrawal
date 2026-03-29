<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

interface KafkaConsumerTransport
{
    /**
     * @param list<string> $topics
     */
    public function subscribe(array $topics): void;

    public function consume(int $timeoutMs): ?KafkaConsumerMessage;

    public function commit(KafkaConsumerMessage $message): void;
}
