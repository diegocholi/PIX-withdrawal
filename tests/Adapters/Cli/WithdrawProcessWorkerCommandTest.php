<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use PHPUnit\Framework\Assert;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\WithdrawProcessWorkerCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliCorrelationIdResolver;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionContext;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WorkerRunResult;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WorkerRuntimeOptions;
use Tecnofit\PixWithdrawal\Adapters\Cli\Worker\WithdrawProcessingWorker;
use Tecnofit\PixWithdrawal\Core\Application\Exception\TransientInfrastructureException;
use Tecnofit\PixWithdrawal\Providers\Config\KafkaConfig;

final class WithdrawProcessWorkerCommandTest extends \PHPUnit\Framework\TestCase
{
    public function testCommandRunsWorkerAndPrintsOperationalSummary(): void
    {
        $command = new WithdrawProcessWorkerCommand(new class extends WithdrawProcessingWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                Assert::assertSame(3, $options->maxMessages());
                Assert::assertSame('custom-withdraw-group', $options->groupId());
                Assert::assertSame('custom.withdraw.process', $options->topic());
                Assert::assertSame(2500, $options->pollTimeoutMs());
                Assert::assertSame('corr-cli-worker-process', $options->correlationId());

                return new WorkerRunResult(3, false);
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-process'));

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([
            '--max-messages' => '3',
            '--group-id' => 'custom-withdraw-group',
            '--topic' => 'custom.withdraw.process',
            '--poll-timeout' => '2500',
        ]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('withdraw:worker:process', $payload['command']);
        self::assertSame('ok', $payload['status']);
        self::assertSame(3, $payload['max_messages']);
        self::assertSame('custom-withdraw-group', $payload['group_id']);
        self::assertSame('custom.withdraw.process', $payload['topic']);
        self::assertSame(2500, $payload['poll_timeout_ms']);
        self::assertSame(3, $payload['processed_messages']);
    }

    public function testCommandReturnsInvalidArgumentForNegativeMaxMessages(): void
    {
        $command = new WithdrawProcessWorkerCommand(new class extends WithdrawProcessingWorker {
            public function __construct()
            {
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-process'));

        $tester = new CommandTester($command);

        self::assertSame(2, $tester->execute(['--max-messages' => '-1']));
        self::assertStringContainsString(
            'The --max-messages option must be an integer greater than or equal to zero.',
            $tester->getDisplay(),
        );
    }

    public function testCommandReturnsInterruptedExitCodeWhenWorkerStopsBySignal(): void
    {
        $command = new WithdrawProcessWorkerCommand(new class extends WithdrawProcessingWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                return new WorkerRunResult(0, true);
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-process'));

        $tester = new CommandTester($command);
        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(130, $exitCode);
        self::assertSame('interrupted', $payload['status']);
    }

    public function testCommandMapsTransientInfrastructureFailureToDependencyExitCode(): void
    {
        $command = new WithdrawProcessWorkerCommand(new class extends WithdrawProcessingWorker {
            public function __construct()
            {
            }

            public function run(WorkerRuntimeOptions $options): WorkerRunResult
            {
                throw new TransientInfrastructureException('Kafka is unavailable.');
            }
        }, $this->kafkaConfig(), $this->executionCorrelationProvider('corr-cli-worker-process'));

        $tester = new CommandTester($command);

        self::assertSame(3, $tester->execute(['--max-messages' => '1']));
        self::assertStringContainsString('Kafka is unavailable.', $tester->getDisplay());
    }

    private function kafkaConfig(): KafkaConfig
    {
        return KafkaConfig::fromArray([
            'brokers' => ['kafka:19092'],
            'client_id' => 'pix-withdrawal-test',
            'consumer_groups' => [
                'withdraw' => 'pix-withdrawal-test-withdraw',
            ],
            'producers' => [],
            'topics' => [
                KafkaConfig::TOPIC_WITHDRAW_PROCESS => 'withdraw.process',
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
