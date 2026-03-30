<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConsumerFactory;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;

final readonly class WithdrawNotificationWorker
{
    public function __construct(
        private readonly KafkaConsumerFactory $kafkaConsumerFactory,
        private readonly WithdrawNotificationKafkaHandler $withdrawNotificationKafkaHandler,
    ) {
    }

    public function run(WorkerRuntimeOptions $options): WorkerRunResult
    {
        $consumer = $this->kafkaConsumerFactory->create(
            consumerGroupKey: 'notification',
            topicKey: KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW,
            groupIdOverride: $options->groupId(),
            topicOverride: $options->topic(),
            pollTimeoutMsOverride: $options->pollTimeoutMs(),
            executionCorrelationId: $options->correlationId(),
        );

        return new WorkerRunResult(
            processedMessages: $consumer->consume($this->withdrawNotificationKafkaHandler, $options->maxMessages()),
            interrupted: $consumer->wasInterrupted(),
        );
    }
}
