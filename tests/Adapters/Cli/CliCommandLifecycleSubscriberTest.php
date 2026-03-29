<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ConfigInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap\CliCommandLifecycleSubscriber;
use Tecnofit\PixWithdrawal\Adapters\Cli\Command\BootstrapSanityCheckCommand;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliCorrelationIdResolver;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionContext;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Core\Shared\MetricPoint;

final class CliCommandLifecycleSubscriberTest extends TestCase
{
    public function testSubscriberLogsStartAndSuccessfulFinishForCliCommand(): void
    {
        $observability = new InMemoryCliObservability();
        $executionContext = new CliExecutionContext();
        $subscriber = new CliCommandLifecycleSubscriber(
            $observability,
            new CliExecutionCorrelationProvider(
                $executionContext,
                new CliCorrelationIdResolver(new class implements CorrelationIdGenerator {
                    public function generate(): string
                    {
                        return 'corr-cli-success';
                    }
                }),
            ),
            $executionContext,
        );

        $command = new BootstrapSanityCheckCommand(
            $this->createMock(ConfigInterface::class),
            new class implements \Psr\Clock\ClockInterface {
                public function now(): \DateTimeImmutable
                {
                    return new \DateTimeImmutable('2026-03-29T12:00:00+00:00');
                }
            },
        );
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $subscriber->onCommand(new ConsoleCommandEvent($command, $input, $output));
        usleep(1_000);
        $subscriber->onTerminate(new ConsoleTerminateEvent($command, $input, $output, CliExitCode::SUCCESS));

        self::assertCount(2, $observability->infoCalls);
        self::assertSame('cli.command.started', $observability->infoCalls[0]['message']);
        self::assertSame('started', $observability->infoCalls[0]['context']->toArray()['status']);
        self::assertSame('app:sanity-check', $observability->infoCalls[0]['context']->toArray()['context']['command']);
        self::assertSame('cli.command.finished', $observability->infoCalls[1]['message']);
        self::assertSame('success', $observability->infoCalls[1]['context']->toArray()['status']);
        self::assertSame(0, $observability->infoCalls[1]['context']->toArray()['context']['exit_code']);
        self::assertIsInt($observability->infoCalls[1]['context']->toArray()['context']['duration_ms']);
        self::assertSame('corr-cli-success', $observability->infoCalls[1]['context']->toArray()['correlation_id']);
        self::assertNull($executionContext->correlationId());
    }

    public function testSubscriberReusesExplicitCorrelationIdProvidedByCliExecution(): void
    {
        $observability = new InMemoryCliObservability();
        $executionContext = new CliExecutionContext();
        $subscriber = new CliCommandLifecycleSubscriber(
            $observability,
            new CliExecutionCorrelationProvider(
                $executionContext,
                new CliCorrelationIdResolver(new class implements CorrelationIdGenerator {
                    public function generate(): string
                    {
                        return 'corr-cli-generated';
                    }
                }),
            ),
            $executionContext,
        );

        $command = new BootstrapSanityCheckCommand(
            $this->createMock(ConfigInterface::class),
            new class implements \Psr\Clock\ClockInterface {
                public function now(): \DateTimeImmutable
                {
                    return new \DateTimeImmutable('2026-03-29T12:00:00+00:00');
                }
            },
        );
        $input = new ArrayInput(['--correlation-id' => 'corr-cli-explicit']);
        $output = new BufferedOutput();

        $subscriber->onCommand(new ConsoleCommandEvent($command, $input, $output));
        $subscriber->onTerminate(new ConsoleTerminateEvent($command, $input, $output, CliExitCode::SUCCESS));

        self::assertSame('corr-cli-explicit', $observability->infoCalls[0]['context']->toArray()['correlation_id']);
        self::assertSame('corr-cli-explicit', $observability->infoCalls[1]['context']->toArray()['correlation_id']);
        self::assertNull($executionContext->correlationId());
    }

    public function testSubscriberLogsRuntimeFailureAsErrorWhenCommandRaisesUnhandledException(): void
    {
        $observability = new InMemoryCliObservability();
        $executionContext = new CliExecutionContext();
        $subscriber = new CliCommandLifecycleSubscriber(
            $observability,
            new CliExecutionCorrelationProvider(
                $executionContext,
                new CliCorrelationIdResolver(new class implements CorrelationIdGenerator {
                    public function generate(): string
                    {
                        return 'corr-cli-failure';
                    }
                }),
            ),
            $executionContext,
        );

        $command = new BootstrapSanityCheckCommand(
            $this->createMock(ConfigInterface::class),
            new class implements \Psr\Clock\ClockInterface {
                public function now(): \DateTimeImmutable
                {
                    return new \DateTimeImmutable('2026-03-29T12:00:00+00:00');
                }
            },
        );
        $input = new ArrayInput([]);
        $output = new BufferedOutput();

        $subscriber->onCommand(new ConsoleCommandEvent($command, $input, $output));
        $subscriber->onError(new ConsoleErrorEvent($input, $output, new \RuntimeException('Unhandled CLI failure.'), $command));
        $subscriber->onTerminate(new ConsoleTerminateEvent($command, $input, $output, CliExitCode::RUNTIME_FAILURE));

        self::assertCount(1, $observability->errorCalls);
        self::assertSame('cli.command.finished', $observability->errorCalls[0]['message']);
        self::assertSame('runtime_failure', $observability->errorCalls[0]['context']->toArray()['status']);
        self::assertSame(
            'Unhandled CLI failure.',
            $observability->errorCalls[0]['context']->toArray()['context']['error_message']
        );
        self::assertNull($executionContext->correlationId());
    }
}

final class InMemoryCliObservability implements Observability
{
    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $infoCalls = [];

    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $warningCalls = [];

    /**
     * @var list<array{message: string, context: LogContext}>
     */
    public array $errorCalls = [];

    public function info(string $message, LogContext $context): void
    {
        $this->infoCalls[] = ['message' => $message, 'context' => $context];
    }

    public function warning(string $message, LogContext $context): void
    {
        $this->warningCalls[] = ['message' => $message, 'context' => $context];
    }

    public function error(string $message, LogContext $context): void
    {
        $this->errorCalls[] = ['message' => $message, 'context' => $context];
    }

    public function increment(MetricPoint $metricPoint): void
    {
    }
}
