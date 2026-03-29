<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\WithdrawNotifyWorkerCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliCorrelationIdResolver;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionContext;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WorkerRunResult;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WorkerRuntimeOptions;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawNotificationWorker;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

final class WithdrawNotifyWorkerCommandTest extends TestCase
{
    public function testCommandRunsNotificationWorkerAndPrintsOperationalSummary(): void
    {
        $command = new WithdrawNotifyWorkerCommand(new class extends WithdrawNotificationWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                TestCase::assertSame(2, $options->maxMessages());
                TestCase::assertSame('custom-notify-group', $options->groupId());
                TestCase::assertSame('custom.notification.withdraw', $options->topic());
                TestCase::assertSame(1500, $options->pollTimeoutMs());
                TestCase::assertSame('corr-cli-worker-notify', $options->correlationId());

                return new WorkerRunResult(2, false);
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-notify'));

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--max-messages' => '2',
            '--group-id' => 'custom-notify-group',
            '--topic' => 'custom.notification.withdraw',
            '--poll-timeout' => '1500',
        ]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('withdraw:worker:notify', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertSame(2, $payload['max_messages']);
        self::assertSame('custom-notify-group', $payload['group_id']);
        self::assertSame('custom.notification.withdraw', $payload['topic']);
        self::assertSame(1500, $payload['poll_timeout_ms']);
        self::assertSame(2, $payload['processed_messages']);
    }

    public function testCommandReturnsInvalidArgumentForNegativeMaxMessages(): void
    {
        $command = new WithdrawNotifyWorkerCommand(new class extends WithdrawNotificationWorker {
            public function __construct()
            {
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-notify'));

        $tester = new CommandTester($command);

        self::assertSame(2, $tester->execute(['--max-messages' => '-1']));
        self::assertStringContainsString(
            'The --max-messages option must be an integer greater than or equal to zero.',
            $tester->getDisplay(),
        );
    }

    public function testCommandReturnsInterruptedExitCodeWhenWorkerStopsBySignal(): void
    {
        $command = new WithdrawNotifyWorkerCommand(new class extends WithdrawNotificationWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                return new WorkerRunResult(0, true);
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-notify'));

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(130, $exitCode);
        self::assertSame('interrupted', $payload['status']);
    }

    public function testCommandMapsTransientInfrastructureFailureToDependencyExitCode(): void
    {
        $command = new WithdrawNotifyWorkerCommand(new class extends WithdrawNotificationWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                throw new TransientInfrastructureException('SMTP worker dependency is unavailable.');
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-notify'));

        $tester = new CommandTester($command);

        self::assertSame(3, $tester->execute(['--max-messages' => '1']));
        self::assertStringContainsString('SMTP worker dependency is unavailable.', $tester->getDisplay());
    }

    private function kafkaConfig(): KafkaConfig
    {
        return KafkaConfig::fromArray([
            'brokers' => ['kafka:19092'],
            'client_id' => 'pix-withdrawal-test',
            'consumer_groups' => [
                'notification' => 'pix-withdrawal-test-notification',
            ],
            'producers' => [],
            'topics' => [
                KafkaConfig::TOPIC_NOTIFICATION_EMAIL_WITHDRAW => 'notification.email.withdraw',
            ],
            'operation_timeout_ms' => 1000,
            'flush_timeout_ms' => 5000,
            'required_topic_keys' => [],
        ]);
    }

    private function executionCorrelationProvider(string $correlationId): CliExecutionCorrelationProvider
    {
        $context = new CliExecutionContext();
        $context->activate($correlationId);

        return new CliExecutionCorrelationProvider(
            $context,
            new CliCorrelationIdResolver(new class implements \Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator {
                public function generate(): string
                {
                    return 'generated-cli-correlation-id';
                }
            }),
        );
    }
}
