<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;
use Tecnofit\PixWithdrawal\Providers\Factories\KafkaConsumerFactory;

class WithdrawProcessingWorker
{
    public function __construct(
        private readonly KafkaConsumerFactory $kafkaConsumerFactory,
        private readonly WithdrawProcessingKafkaHandler $withdrawProcessingKafkaHandler,
    ) {
    }

    public function run(WorkerRuntimeOptions $options): WorkerRunResult
    {
        $consumer = $this->kafkaConsumerFactory->create(
            consumerGroupKey: 'withdraw',
            topicKey: KafkaConfig::TOPIC_WITHDRAW_PROCESS,
            groupIdOverride: $options->groupId(),
            topicOverride: $options->topic(),
            pollTimeoutMsOverride: $options->pollTimeoutMs(),
            executionCorrelationId: $options->correlationId(),
        );

        return new WorkerRunResult(
            processedMessages: $consumer->consume($this->withdrawProcessingKafkaHandler, $options->maxMessages()),
            interrupted: $consumer->wasInterrupted(),
        );
    }
}
