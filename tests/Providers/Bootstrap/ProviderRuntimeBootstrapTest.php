<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Bootstrap;

use PHPUnit\Framework\TestCase;
use Tecnofit\PixWithdrawal\Providers\Bootstrap\ProviderRuntimeBootstrap;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;

final class ProviderRuntimeBootstrapTest extends TestCase
{
    public function testBootstrapBuildsLocalProfile(): void
    {
        $config = (new ProviderRuntimeBootstrap())->bootstrap('local');

        self::assertSame('local', $config['runtime']['environment']);
        self::assertArrayHasKey('http', $config['runtime']['required_bindings']);
        self::assertArrayHasKey('worker', $config['runtime']['required_bindings']);
        self::assertContains(SmtpWithdrawMailer::class, $config['runtime']['required_bindings']['worker']);
        self::assertContains(DomainEventDispatcher::class, $config['runtime']['required_bindings']['scheduler']);
        self::assertNotContains(SmtpWithdrawMailer::class, $config['runtime']['required_bindings']['scheduler']);
        self::assertSame('hyperf', $config['observability']['driver']);
        self::assertSame('logger', $config['metrics']['driver']);
        self::assertSame('kafka', $config['domain_events']['driver']);
        self::assertSame('system', $config['clock']['driver']);
    }

    public function testBootstrapBuildsTestProfile(): void
    {
        $config = (new ProviderRuntimeBootstrap())->bootstrap('testing');

        self::assertSame('test', $config['runtime']['environment']);
        self::assertSame('null', $config['observability']['driver']);
        self::assertSame('null', $config['metrics']['driver']);
        self::assertSame('null', $config['domain_events']['driver']);
        self::assertSame('test', $config['clock']['driver']);
        self::assertSame('fake', $config['identifiers']['uuid_driver']);
        self::assertSame(60, $config['identifiers']['withdraw_duplicate_guard_window_seconds']);
    }

    public function testBootstrapFallsBackToProductionProfile(): void
    {
        $config = (new ProviderRuntimeBootstrap())->bootstrap('prod');

        self::assertSame('prod', $config['runtime']['environment']);
        self::assertSame('hyperf', $config['observability']['driver']);
        self::assertSame('logger', $config['metrics']['driver']);
        self::assertSame('kafka', $config['domain_events']['driver']);
    }
}
