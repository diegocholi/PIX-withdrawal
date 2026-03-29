<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use Symfony\Component\Console\Tester\CommandTester;

final class WithdrawNotifyWorkerCommandFunctionalTest extends CliCommandIntegrationTestCase
{
    public function testCommandConsumesNotificationMessageDeliversEmailToMailhogAndCommitsOffset(): void
    {
        $recipient = $this->uniqueEmail('notify-worker-functional');
        $workerGroupId = $this->uniqueGroupId('worker-functional-notify');
        $inputTopic = sprintf('it.notification.email.withdraw.%s', bin2hex(random_bytes(8)));
        $businessCorrelationId = 'corr-notify-worker-functional-100';
        $withdrawId = 'wd-notify-worker-functional-100';

        $this->publishRawMessage(
            topic: $inputTopic,
            payload: json_encode([
                'event_name' => 'notification.email.withdraw',
                'correlation_id' => $businessCorrelationId,
                'occurred_at' => '2026-03-28T10:04:00-03:00',
                'payload' => [
                    'recipient' => $recipient,
                    'event' => [
                        'event_name' => 'withdraw.processed',
                        'aggregate_id' => $withdrawId,
                        'occurred_at' => '2026-03-28T10:03:00-03:00',
                        'correlation_id' => 'corr-withdraw-processed-100',
                        'trace_metadata' => [
                            'origin' => 'cli.withdraw_worker',
                            'cli_correlation_id' => 'cli-notify-worker-functional-seed',
                        ],
                        'payload' => [
                            'withdraw_id' => $withdrawId,
                            'account_id' => 'acc-notify-worker-functional-100',
                            'amount' => '1250.50',
                            'method' => 'PIX',
                            'status' => 'DONE',
                            'pix_key_type' => 'EMAIL',
                            'pix_key_masked' => 'u***@example.test',
                        ],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
            key: $withdrawId,
            headers: [
                'correlation_id' => $businessCorrelationId,
                'event_name' => 'notification.email.withdraw',
                'occurred_at' => '2026-03-28T10:04:00-03:00',
            ],
        );

        $tester = $this->commandTester();

        $exitCode = $tester->execute([
            '--max-messages' => '1',
            '--group-id' => $workerGroupId,
            '--topic' => $inputTopic,
        ]);
        $commandPayload = $this->decodeCommandPayload($tester->getDisplay());
        $message = $this->waitForMailhogMessage($recipient, 'Saque PIX concluido');
        $committedMessage = $this->rawKafkaMessage($inputTopic, $workerGroupId, 2000);

        self::assertSame(0, $exitCode);
        self::assertSame('withdraw:worker:notify', $commandPayload['command']);
        self::assertSame('ok', $commandPayload['status']);
        self::assertSame(1, $commandPayload['max_messages']);
        self::assertSame($workerGroupId, $commandPayload['group_id']);
        self::assertSame($inputTopic, $commandPayload['topic']);
        self::assertSame(1, $commandPayload['processed_messages']);
        self::assertNotNull($message, 'MailHog did not receive the notification email within the expected timeout.');
        self::assertSame([$recipient], $message['Raw']['To'] ?? null);
        self::assertSame('Saque PIX concluido', $message['Content']['Headers']['Subject'][0] ?? null);
        self::assertStringContainsString(
            'Sua solicitacao de saque PIX foi concluida.',
            (string) ($message['Content']['Body'] ?? ''),
        );
        self::assertStringContainsString(
            'Valor sacado: R$ 1.250,50',
            (string) ($message['Content']['Body'] ?? ''),
        );
        self::assertStringContainsString(
            'Chave PIX: u***@example.test',
            (string) ($message['Content']['Body'] ?? ''),
        );
        self::assertNull($committedMessage, 'Worker should commit the processed notification offset for the same consumer group.');
    }

    protected function commandTester(): CommandTester
    {
        $application = $this->container->get(ApplicationInterface::class);

        return new CommandTester($application->find('withdraw:worker:notify'));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCommandPayload(string $display): array
    {
        $lines = preg_split('/\R/', trim($display)) ?: [];
        $jsonLine = null;

        foreach (array_reverse($lines) as $line) {
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, '{') && str_ends_with($trimmedLine, '}')) {
                $jsonLine = $trimmedLine;
                break;
            }
        }

        self::assertIsString($jsonLine, sprintf('Could not find JSON payload in command output: %s', $display));

        /** @var array<string, mixed> $payload */
        $payload = json_decode($jsonLine, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }
}
