<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use PHPUnit\Framework\TestCase;

final class WorkerCommandSignalShutdownFunctionalTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('APP_ENV=local');
        putenv('KAFKA_BROKERS=kafka:19092');
    }

    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('KAFKA_BROKERS');

        parent::tearDown();
    }

    public function testWithdrawProcessWorkerCommandStopsGracefullyWhenReceivingSigint(): void
    {
        $result = $this->runUntilSigint([
            'php',
            'bin/hyperf.php',
            'withdraw:worker:process',
            '--group-id=' . $this->uniqueGroupId('signal-process'),
            '--topic=withdraw.process',
            '--poll-timeout=200',
        ]);

        self::assertSame(130, $result['exit_code']);
        self::assertSame('withdraw:worker:process', $result['payload']['command']);
        self::assertSame('interrupted', $result['payload']['status']);
        self::assertSame(0, $result['payload']['processed_messages']);
    }

    public function testWithdrawNotifyWorkerCommandStopsGracefullyWhenReceivingSigint(): void
    {
        $result = $this->runUntilSigint([
            'php',
            'bin/hyperf.php',
            'withdraw:worker:notify',
            '--group-id=' . $this->uniqueGroupId('signal-notify'),
            '--topic=withdraw.process',
            '--poll-timeout=200',
        ]);

        self::assertSame(130, $result['exit_code']);
        self::assertSame('withdraw:worker:notify', $result['payload']['command']);
        self::assertSame('interrupted', $result['payload']['status']);
        self::assertSame(0, $result['payload']['processed_messages']);
    }

    /**
     * @param list<string> $command
     * @return array{exit_code:int, payload:array<string, mixed>}
     */
    private function runUntilSigint(array $command): array
    {
        if (! function_exists('proc_open') || ! function_exists('proc_terminate')) {
            self::markTestSkipped('proc_open and proc_terminate are required for signal shutdown functional tests.');
        }

        $process = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            dirname(__DIR__, 3),
            [
                'APP_ENV' => 'test',
                'KAFKA_BROKERS' => 'kafka:19092',
            ],
        );

        self::assertIsResource($process);
        fclose($pipes[0]);

        usleep(750000);
        proc_terminate($process, 2);

        $deadline = microtime(true) + 10;
        $status = proc_get_status($process);

        while (($status['running'] ?? false) === true && microtime(true) < $deadline) {
            usleep(100000);
            $status = proc_get_status($process);
        }

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = (int) ($status['exitcode'] ?? -1);
        $closeCode = proc_close($process);

        if ($exitCode < 0) {
            $exitCode = $closeCode;
        }

        return [
            'exit_code' => $exitCode,
            'payload' => $this->decodeCommandPayload($stdout . PHP_EOL . $stderr),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeCommandPayload(string $output): array
    {
        $lines = preg_split('/\R/', trim($output)) ?: [];
        $jsonLine = null;

        foreach (array_reverse($lines) as $line) {
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, '{') && str_ends_with($trimmedLine, '}')) {
                $jsonLine = $trimmedLine;
                break;
            }
        }

        self::assertIsString($jsonLine, sprintf('Could not find JSON payload in worker command output: %s', $output));

        /** @var array<string, mixed> $payload */
        $payload = json_decode($jsonLine, true, 512, JSON_THROW_ON_ERROR);

        return $payload;
    }

    private function uniqueGroupId(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, bin2hex(random_bytes(8)));
    }
}
