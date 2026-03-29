<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use RdKafka\Producer;
use RdKafka\ProducerTopic;

final readonly class RdkafkaProducer implements KafkaProducerTransport
{
    private const FLUSH_SLICE_MS = 250;

    public function __construct(
        private Producer $producer,
        private int $flushTimeoutMs,
    ) {
    }

    public function publish(KafkaProducerRecord $record): void
    {
        try {
            $topic = $this->producer->newTopic($record->topic());
            $this->produce($topic, $record);
            $this->drainProducerQueue($record);
        } catch (KafkaPublishException $exception) {
            throw $exception;
        } catch (\Throwable $throwable) {
            throw KafkaPublishException::transportFailure($record->topic(), $throwable);
        }
    }

    private function produce(ProducerTopic $topic, KafkaProducerRecord $record): void
    {
        $topic->producev(
            $record->partition(),
            0,
            $record->payload(),
            $record->key(),
            $record->headers(),
        );
    }

    private function drainProducerQueue(KafkaProducerRecord $record): void
    {
        $startedAt = microtime(true);

        do {
            $sliceMs = $this->nextFlushSliceMs($startedAt);

            if ($sliceMs <= 0) {
                throw KafkaPublishException::flushFailed($record->topic(), RD_KAFKA_RESP_ERR__TIMED_OUT);
            }

            $this->producer->poll(0);
            $flushResult = $this->producer->flush($sliceMs);

            if ($flushResult === RD_KAFKA_RESP_ERR_NO_ERROR) {
                return;
            }

            if ($flushResult !== RD_KAFKA_RESP_ERR__TIMED_OUT) {
                throw KafkaPublishException::flushFailed($record->topic(), $flushResult);
            }
        } while (true);
    }

    private function nextFlushSliceMs(float $startedAt): int
    {
        $elapsedMs = (int) floor((microtime(true) - $startedAt) * 1000);
        $remainingMs = $this->flushTimeoutMs - $elapsedMs;

        return max(0, min(self::FLUSH_SLICE_MS, $remainingMs));
    }
}
