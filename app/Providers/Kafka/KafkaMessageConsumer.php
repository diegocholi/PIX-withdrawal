<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Providers\Kafka;

use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final class KafkaMessageConsumer
{
    private bool $stopRequested = false;
    private bool $interruptedBySignal = false;

    public function __construct(
        private readonly KafkaConsumerTransport $transport,
        private readonly StructuredLogger $structuredLogger,
        private readonly string $topic,
        private readonly int $pollTimeoutMs,
        private readonly ?KafkaConsumerSignalListener $signalListener = null,
        private readonly ?KafkaConsumeFailurePolicy $failurePolicy = null,
        private readonly ?string $executionCorrelationId = null,
    ) {
    }

    public function requestStop(): void
    {
        $this->stopRequested = true;
    }

    public function wasInterrupted(): bool
    {
        return $this->interruptedBySignal;
    }

    public function consume(KafkaConsumerHandler $handler, int $maxMessages = 0): int
    {
        $this->stopRequested = false;
        $this->interruptedBySignal = false;

        $this->signalListener?->listen(function (): void {
            $this->interruptedBySignal = true;
            $this->requestStop();
        });

        $this->transport->subscribe([$this->topic]);

        $this->structuredLogger->info(
            'kafka.consume.started',
            new LogContext(
                correlationId: $this->executionCorrelationId(),
                context: [
                    'provider' => 'kafka',
                    'operation' => 'kafka.consume',
                    'topic' => $this->topic,
                ],
            ),
        );

        $processedMessages = 0;

        while (! $this->stopRequested && ($maxMessages === 0 || $processedMessages < $maxMessages)) {
            try {
                $message = $this->transport->consume($this->pollTimeoutMs);
            } catch (\Throwable $throwable) {
                $this->failurePolicy()->handlePollFailure($this->topic, $throwable);
            }

            if ($message === null) {
                continue;
            }

            $this->structuredLogger->info(
                'kafka.consume.received',
                new LogContext(
                    correlationId: $message->correlationId(),
                    context: [
                        'provider' => 'kafka',
                        'operation' => 'kafka.consume',
                        'topic' => $message->topic(),
                        'partition' => $message->partition(),
                        'event_name' => $message->eventName(),
                    ],
                ),
            );

            try {
                $handler->handle($message);
                $this->transport->commit($message);
            } catch (\Throwable $throwable) {
                $this->failurePolicy()->handleMessageFailure($message, $throwable);
            }
            $processedMessages++;
        }

        $this->structuredLogger->info(
            'kafka.consume.stopped',
            new LogContext(
                correlationId: $this->executionCorrelationId(),
                context: [
                    'provider' => 'kafka',
                    'operation' => 'kafka.consume',
                    'topic' => $this->topic,
                    'processed_messages' => $processedMessages,
                ],
            ),
        );

        return $processedMessages;
    }

    private function failurePolicy(): KafkaConsumeFailurePolicy
    {
        return $this->failurePolicy ?? new KafkaConsumeFailurePolicy($this->structuredLogger);
    }

    private function executionCorrelationId(): string
    {
        $correlationId = $this->executionCorrelationId;

        if ($correlationId === null || trim($correlationId) === '') {
            return 'kafka-consumer';
        }

        return trim($correlationId);
    }
}
