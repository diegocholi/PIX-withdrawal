<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Worker;

use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdraw;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerHandler;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final readonly class WithdrawProcessingKafkaHandler implements KafkaConsumerHandler
{
    public function __construct(
        private WithdrawProcessingKafkaMessageMapper $withdrawProcessingKafkaMessageMapper,
        private ProcessWithdraw $processWithdraw,
    ) {
    }

    public function handle(KafkaConsumerMessage $message): void
    {
        $this->processWithdraw->execute(
            $this->withdrawProcessingKafkaMessageMapper->map($message)
        );
    }
}
