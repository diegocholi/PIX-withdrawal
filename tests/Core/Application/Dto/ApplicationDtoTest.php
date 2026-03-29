<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Core\Application\Dto;

use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Application\Command\ProcessWithdrawInput;
use Tecnofit\PixWithdrawal\Core\Application\Command\PublishDomainEventInput;
use Tecnofit\PixWithdrawal\Core\Application\Dto\CreateWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\FindWithdrawStatusData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\ProcessWithdrawData;
use Tecnofit\PixWithdrawal\Core\Application\Dto\PublishDomainEventData;
use Tecnofit\PixWithdrawal\Core\Application\Query\FindWithdrawStatusInput;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SerializableDto;

final class ApplicationDtoTest extends TestCase
{
    public function testCreateWithdrawInputIsReadonlyAndSerializable(): void
    {
        $input = new CreateWithdrawInput('acc-1', 'corr-1', 'PIX', 'EMAIL', 'user@example.com', '100.00', '2026-03-29T10:00:00-03:00', ['trace_id' => 'trace-1']);

        self::assertTrue((new ReflectionClass($input))->isReadOnly());
        self::assertInstanceOf(SerializableDto::class, $input);
        self::assertSame(
            [
                'account_id' => 'acc-1',
                'correlation_id' => 'corr-1',
                'method' => 'PIX',
                'pix_key_type' => 'EMAIL',
                'pix_key' => 'user@example.com',
                'amount' => '100.00',
                'schedule_at' => '2026-03-29T10:00:00-03:00',
                'trace_metadata' => ['trace_id' => 'trace-1'],
            ],
            $input->toArray()
        );
    }

    public function testProcessWithdrawInputIsReadonlyAndSerializable(): void
    {
        $input = new ProcessWithdrawInput('wd-1', 'corr-2', 2, ['trace_id' => 'trace-2']);

        self::assertTrue((new ReflectionClass($input))->isReadOnly());
        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'correlation_id' => 'corr-2',
                'attempt' => 2,
                'trace_metadata' => ['trace_id' => 'trace-2'],
            ],
            $input->toArray()
        );
    }

    public function testPublishDomainEventInputIsReadonlyAndSerializable(): void
    {
        $input = new PublishDomainEventInput('withdraw.created', ['withdraw_id' => 'wd-1'], 'corr-3', ['trace_id' => 'trace-3']);

        self::assertTrue((new ReflectionClass($input))->isReadOnly());
        self::assertSame(
            [
                'event_name' => 'withdraw.created',
                'payload' => ['withdraw_id' => 'wd-1'],
                'correlation_id' => 'corr-3',
                'trace_metadata' => ['trace_id' => 'trace-3'],
            ],
            $input->toArray()
        );
    }

    public function testFindWithdrawStatusInputIsReadonlyAndSerializable(): void
    {
        $input = new FindWithdrawStatusInput('acc-2', 'wd-2', 'corr-4', ['trace_id' => 'trace-4']);

        self::assertTrue((new ReflectionClass($input))->isReadOnly());
        self::assertSame(
            [
                'account_id' => 'acc-2',
                'withdraw_id' => 'wd-2',
                'correlation_id' => 'corr-4',
                'trace_metadata' => ['trace_id' => 'trace-4'],
            ],
            $input->toArray()
        );
    }

    public function testOutputsAreReadonlyAndSerializable(): void
    {
        $create = new CreateWithdrawData('wd-1', 'corr-1', 'QUEUED', ['trace_id' => 'trace-1']);
        $process = new ProcessWithdrawData('wd-1', 'corr-2', 'DONE', ['trace_id' => 'trace-2']);
        $status = new FindWithdrawStatusData('wd-1', 'PROCESSING', '100.00', 'PIX', false, null, null, null, 'EMAIL', 'u***@example.com', 'corr-3', ['trace_id' => 'trace-3']);
        $event = new PublishDomainEventData('withdraw.done', 'corr-4', true, ['trace_id' => 'trace-4']);

        self::assertTrue((new ReflectionClass($create))->isReadOnly());
        self::assertTrue((new ReflectionClass($process))->isReadOnly());
        self::assertTrue((new ReflectionClass($status))->isReadOnly());
        self::assertTrue((new ReflectionClass($event))->isReadOnly());

        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'correlation_id' => 'corr-1',
                'status' => 'QUEUED',
                'trace_metadata' => ['trace_id' => 'trace-1'],
            ],
            $create->toArray()
        );
        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'correlation_id' => 'corr-2',
                'status' => 'DONE',
                'trace_metadata' => ['trace_id' => 'trace-2'],
            ],
            $process->toArray()
        );
        self::assertSame(
            [
                'withdraw_id' => 'wd-1',
                'status' => 'PROCESSING',
                'amount' => '100.00',
                'method' => 'PIX',
                'scheduled' => false,
                'scheduled_for' => null,
                'processed_at' => null,
                'error_reason' => null,
                'pix_key_type' => 'EMAIL',
                'pix_key_masked' => 'u***@example.com',
                'correlation_id' => 'corr-3',
                'trace_metadata' => ['trace_id' => 'trace-3'],
            ],
            $status->toArray()
        );
        self::assertSame(
            [
                'event_name' => 'withdraw.done',
                'correlation_id' => 'corr-4',
                'published' => true,
                'trace_metadata' => ['trace_id' => 'trace-4'],
            ],
            $event->toArray()
        );
    }
}
