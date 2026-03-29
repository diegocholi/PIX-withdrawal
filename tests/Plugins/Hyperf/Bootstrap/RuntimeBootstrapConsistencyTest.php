<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Hyperf\Bootstrap;

use Hyperf\Contract\ApplicationInterface;
use Hyperf\Testing\Http\Client;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Swoole\Coroutine;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfRuntimeBootstrap;

final class RuntimeBootstrapConsistencyTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testHttpAndCliShareTheSameBootstrapConfiguration(): void
    {
        putenv('APP_NAME=shared-runtime-bootstrap');
        putenv('APP_TIMEZONE=UTC');

        $container = (new HyperfRuntimeBootstrap())->bootContainer();
        $httpClient = new Client($container);
        $httpResponse = null;

        Coroutine\run(static function () use ($httpClient, &$httpResponse): void {
            $httpResponse = $httpClient->get('/');
        });

        self::assertInstanceOf(ResponseInterface::class, $httpResponse);

        $httpPayload = json_decode((string) $httpResponse->getBody(), true, 512, JSON_THROW_ON_ERROR);

        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('app:sanity-check');
        $tester = new CommandTester($command);
        $tester->execute([]);

        $cliPayload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('shared-runtime-bootstrap', $httpPayload['name']);
        self::assertSame('shared-runtime-bootstrap', $cliPayload['name']);
        self::assertSame('ok', $httpPayload['status']);
        self::assertSame('ok', $cliPayload['status']);
        self::assertSame('http', $httpPayload['runtime']);
        self::assertSame('cli', $cliPayload['runtime']);
        self::assertStringEndsWith('+00:00', $cliPayload['timestamp']);
    }
}
