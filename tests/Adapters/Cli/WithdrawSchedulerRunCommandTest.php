<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\DbConnection\Db;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdraw;
use Tecnofit\PixWithdrawal\Core\Domain\Entity\AccountWithdrawPix;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\PixKeyType;
use Tecnofit\PixWithdrawal\Core\Domain\Enum\WithdrawMethod;
use Tecnofit\PixWithdrawal\Core\Domain\ValueObject\PixKey;

final class WithdrawSchedulerRunCommandTest extends CliCommandIntegrationTestCase
{
    public function testCommandPromotesAndPublishesDueScheduledWithdrawsWithinBatchSize(): void
    {
        $this->insertAccount('acc-scheduler-100');
        $this->insertAccount('acc-scheduler-101');
        $this->insertAccount('acc-scheduler-102');

        $correlationId = 'corr-wd-scheduler-100';
        $this->persistWithdraw($this->scheduledWithdraw(
            'wd-scheduler-100',
            'acc-scheduler-100',
            '2000-01-01T00:00:00+00:00',
            $correlationId,
        ));
        $this->persistWithdraw($this->scheduledWithdraw('wd-scheduler-101', 'acc-scheduler-101', '2000-01-02T00:00:00+00:00'));
        $this->persistWithdraw($this->scheduledWithdraw('wd-scheduler-102', 'acc-scheduler-102', '2999-01-01T00:00:00+00:00'));
        $this->persistWithdrawPix(AccountWithdrawPix::create(
            withdrawId: 'wd-scheduler-100',
            method: WithdrawMethod::PIX,
            pixKey: PixKey::from(PixKeyType::EMAIL, 'queue@example.com'),
            createdAt: new \DateTimeImmutable('2026-03-28T13:00:00+00:00'),
        ));

        $tester = $this->commandTester();

        $exitCode = $tester->execute(['--batch-size' => '1', '--force' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('ok', $payload['status']);
        self::assertSame(1, $payload['batch_size']);
        self::assertSame(1, $payload['summary']['due']);
        self::assertSame(1, $payload['summary']['promoted']);
        self::assertSame(1, $payload['summary']['published']);
        self::assertSame(0, $payload['summary']['skipped']);
        self::assertSame(0, $payload['summary']['publication_failed']);
        self::assertSame(['wd-scheduler-100'], $payload['withdraw_ids']['promoted']);
        self::assertSame(['wd-scheduler-100'], $payload['withdraw_ids']['published']);
        self::assertSame([], $payload['withdraw_ids']['publication_failed']);
        self::assertSame('QUEUED', Db::table('account_withdraw')->where('id', 'wd-scheduler-100')->value('status'));
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', 'wd-scheduler-101')->value('status'));
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', 'wd-scheduler-102')->value('status'));
        self::assertSame($correlationId, Db::table('account_withdraw')->where('id', 'wd-scheduler-100')->value('correlation_id'));
    }

    public function testCommandRejectsStateChangingExecutionWithoutForce(): void
    {
        $this->insertAccount('acc-scheduler-200');
        $this->persistWithdraw($this->scheduledWithdraw(
            'wd-scheduler-200',
            'acc-scheduler-200',
            '2000-01-01T00:00:00+00:00',
        ));

        $tester = $this->commandTester();

        $exitCode = $tester->execute(['--batch-size' => '1']);

        self::assertSame(2, $exitCode);
        self::assertStringContainsString(
            'The command "withdraw:scheduler:run" changes application state and requires --force.',
            $tester->getDisplay(),
        );
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', 'wd-scheduler-200')->value('status'));
    }

    public function testCommandAllowsDryRunWithoutForce(): void
    {
        $this->insertAccount('acc-scheduler-300');
        $this->persistWithdraw($this->scheduledWithdraw(
            'wd-scheduler-300',
            'acc-scheduler-300',
            '2000-01-01T00:00:00+00:00',
        ));

        $tester = $this->commandTester();

        $exitCode = $tester->execute(['--batch-size' => '1', '--dry-run' => true]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertTrue($payload['dry_run']);
        self::assertSame(['wd-scheduler-300'], $payload['withdraw_ids']['due']);
        self::assertSame('SCHEDULED', Db::table('account_withdraw')->where('id', 'wd-scheduler-300')->value('status'));
    }
}
