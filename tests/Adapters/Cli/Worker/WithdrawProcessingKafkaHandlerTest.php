<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli\Worker;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawProcessingKafkaHandler;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawProcessingKafkaMessageMapper;
use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawCommand;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\UseCase\ProcessWithdraw;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;

final class WithdrawProcessingKafkaHandlerTest extends TestCase
{
    public function testDelegatesMappedMessageToProcessWithdrawHandler(): void
    {
        $useCase = $this->createMock(ProcessWithdraw::class);
        $useCase->expects(self::once())
            ->method('execute')
            ->with(self::callback(static function (ProcessWithdrawCommand $command): bool {
                return $command->withdrawId() === 'wd-1'
                    && $command->correlationId() === 'corr-1'
                    && $command->attempt() === 1;
            }))
            ->willReturn(new ProcessWithdrawData('wd-1', 'corr-1', 'DONE', []));

        $handler = new WithdrawProcessingKafkaHandler(
            new WithdrawProcessingKafkaMessageMapper(),
            $useCase,
        );

        $handler->handle(new KafkaConsumerMessage(
            topic: 'withdraw.process',
            payload: '{"event_name":"withdraw.queued","aggregate_id":"wd-1","occurred_at":"2026-03-29T10:00:00-03:00","correlation_id":"corr-1","payload":{"withdraw_id":"wd-1","account_id":"acc-1","method":"PIX","status":"QUEUED","amount":"10.00","pix_key_type":"EMAIL","queued_at":"2026-03-29T10:00:00-03:00","scheduled_at":null}}',
            key: 'acc-1',
            headers: [],
            partition: 0,
            offset: 1,
        ));
    }
}
