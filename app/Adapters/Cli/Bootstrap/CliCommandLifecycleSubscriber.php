<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Adapters\Cli\Bootstrap;

use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Event\ConsoleErrorEvent;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliCommandExecutionState;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExitCode;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionCorrelationProvider;
use Tecnofit\PixWithdrawal\Adapters\Cli\Support\CliExecutionContext;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;

final class CliCommandLifecycleSubscriber implements EventSubscriberInterface
{
    /**
     * @var array<int, CliCommandExecutionState>
     */
    private array $executionStates = [];

    /**
     * @param list<string> $commandNamespaces
     */
    public function __construct(
        private readonly Observability $observability,
        private readonly CliExecutionCorrelationProvider $executionCorrelationProvider,
        private readonly CliExecutionContext $executionContext,
        private readonly array $commandNamespaces = ['Tecnofit\\PixWithdrawal\\Adapters\\Cli\\Command\\'],
    ) {
    }

    /**
     * @return array<string, string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::COMMAND => 'onCommand',
            ConsoleEvents::ERROR => 'onError',
            ConsoleEvents::TERMINATE => 'onTerminate',
        ];
    }

    public function onCommand(ConsoleCommandEvent $event): void
    {
        $command = $event->getCommand();

        if ($command === null || ! $this->shouldTrack($command::class)) {
            return;
        }

        $state = new CliCommandExecutionState(
            commandName: $command->getName() ?? $command->getDefaultName() ?? $command::class,
            commandClass: $command::class,
            correlationId: $this->executionCorrelationProvider->resolve($event->getInput()),
            startedAtNanoseconds: hrtime(true),
        );
        $this->executionStates[$this->eventKey($event)] = $state;

        $this->observability->info(
            'cli.command.started',
            new LogContext(
                correlationId: $state->correlationId(),
                status: 'started',
                traceMetadata: [
                    'transport' => 'cli',
                    'phase' => 'command_lifecycle',
                ],
                context: [
                    'command' => $state->commandName(),
                    'command_class' => $state->commandClass(),
                    'phase' => 'start',
                ],
            ),
        );
    }

    public function onError(ConsoleErrorEvent $event): void
    {
        $state = $this->executionStates[$this->eventKey($event)] ?? null;

        if ($state === null) {
            return;
        }

        $this->executionStates[$this->eventKey($event)] = $state->withErrorMessage($event->getError()->getMessage());
    }

    public function onTerminate(ConsoleTerminateEvent $event): void
    {
        $eventKey = $this->eventKey($event);
        $state = $this->executionStates[$eventKey] ?? null;

        if ($state === null) {
            return;
        }

        unset($this->executionStates[$eventKey]);
        $this->executionContext->clear();

        $result = $this->resultForExitCode($event->getExitCode());
        $context = new LogContext(
            correlationId: $state->correlationId(),
            status: $result,
            traceMetadata: [
                'transport' => 'cli',
                'phase' => 'command_lifecycle',
            ],
            context: array_filter([
                'command' => $state->commandName(),
                'command_class' => $state->commandClass(),
                'phase' => 'finish',
                'result' => $result,
                'exit_code' => $event->getExitCode(),
                'duration_ms' => $this->durationMilliseconds($state->startedAtNanoseconds()),
                'error_message' => $state->errorMessage(),
            ], static fn (mixed $value): bool => $value !== null),
        );

        match ($result) {
            'runtime_failure' => $this->observability->error('cli.command.finished', $context),
            'success' => $this->observability->info('cli.command.finished', $context),
            default => $this->observability->warning('cli.command.finished', $context),
        };
    }

    private function shouldTrack(string $commandClass): bool
    {
        foreach ($this->commandNamespaces as $commandNamespace) {
            if (str_starts_with($commandClass, $commandNamespace)) {
                return true;
            }
        }

        return false;
    }

    private function eventKey(ConsoleCommandEvent|ConsoleErrorEvent|ConsoleTerminateEvent $event): int
    {
        return spl_object_id($event->getInput());
    }

    private function durationMilliseconds(int $startedAtNanoseconds): int
    {
        return max(0, (int) floor((hrtime(true) - $startedAtNanoseconds) / 1_000_000));
    }

    private function resultForExitCode(int $exitCode): string
    {
        return match ($exitCode) {
            CliExitCode::SUCCESS => 'success',
            CliExitCode::INVALID_ARGUMENT => 'invalid_argument',
            CliExitCode::DEPENDENCY_FAILURE => 'dependency_failure',
            CliExitCode::PARTIAL_FAILURE => 'partial_failure',
            CliExitCode::RUNTIME_FAILURE => 'runtime_failure',
            CliExitCode::INTERRUPTED => 'interrupted',
            ConsoleCommandEvent::RETURN_CODE_DISABLED => 'disabled',
            default => 'completed',
        };
    }
}
