<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Adapters\Cli;

use Hyperf\Contract\ApplicationInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;

final class SystemCheckKafkaCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        putenv('KAFKA_BROKERS=kafka:19092');
    }

    protected function tearDown(): void
    {
        putenv('KAFKA_BROKERS');

        parent::tearDown();
    }

    public function testCommandConfirmsKafkaConnectivityAndClusterMetadata(): void
    {
        $container = (new HyperfContainerFactory())->create();
        $application = $container->get(ApplicationInterface::class);
        $command = $application->find('system:check:kafka');
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $payload = json_decode($tester->getDisplay(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(0, $exitCode);
        self::assertSame('system:check:kafka', $payload['command']);
        self::assertSame('kafka', $payload['check']);
        self::assertSame('ok', $payload['status']);
        self::assertSame('pix-withdrawal-local', $payload['client_id']);
        self::assertSame(1, $payload['configured_brokers_count']);
        self::assertGreaterThanOrEqual(1, $payload['discovered_brokers_count']);
        self::assertGreaterThanOrEqual(1, $payload['discovered_topics_count']);
        self::assertIsInt($payload['origin_broker_id']);
        self::assertIsString($payload['origin_broker_name']);
        self::assertNotSame('', $payload['origin_broker_name']);
    }
}
