<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

final class PcntlKafkaConsumerSignalListener implements KafkaConsumerSignalListener
{
    public function listen(\Closure $stopConsumer): void
    {
        if (! function_exists('pcntl_async_signals') || ! function_exists('pcntl_signal')) {
            return;
        }

        pcntl_async_signals(true);

        foreach ([SIGINT, SIGTERM] as $signal) {
            pcntl_signal($signal, static function () use ($stopConsumer): void {
                $stopConsumer();
            });
        }
    }
}
