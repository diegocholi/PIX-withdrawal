<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Plugins\Hyperf\Bootstrap;

use Hyperf\Context\ApplicationContext;
use Hyperf\Contract\ApplicationInterface;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\Console\Application;
use DateTimeZone;
use Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawCommandValidator;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\AtomicAccountDebit;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DomainEventDispatcher;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\DueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\ScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\TransactionManager;
use Tecnofit\PixWithdrawal\Core\Infrastructure\Contract\WithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Clock;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\FailureClassifier;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\EventPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\MetricEmitter;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\Observability;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\CorrelationIdGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\LogPayloadSerializer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\SensitiveDataMasker;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\UuidGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardFingerprintGenerator;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawDuplicateGuardWindow;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\WithdrawIdempotencyKeyGenerator;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfig;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigProvider;
use Tecnofit\PixWithdrawal\Plugins\Data\Config\MySqlConnectionConfigValidator;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\AccountScopedWithdrawQuery as DataAccountScopedWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\DueScheduledWithdrawQuery as DataDueScheduledWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\ProcessableWithdrawQuery as DataProcessableWithdrawQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Queries\WithdrawStatusViewQuery as DataWithdrawStatusViewQuery;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\MySqlTransactionManager;
use Tecnofit\PixWithdrawal\Plugins\Data\Transactions\ScheduledWithdrawQueuePromotion as DataScheduledWithdrawQueuePromotion;
use Tecnofit\PixWithdrawal\Plugins\Hyperf\Bootstrap\HyperfContainerFactory;
use Tecnofit\PixWithdrawal\Plugins\Observability\HyperfObservability;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaDomainEventDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\SmtpWithdrawMailer;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationDispatcher;
use Tecnofit\PixWithdrawal\Providers\Mail\WithdrawNotificationKafkaHandler;

final class HyperfContainerFactoryTest extends TestCase
{
    protected function tearDown(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_LOG_LEVEL');
        putenv('APP_LOCALE');
        putenv('APP_FALLBACK_LOCALE');
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        parent::tearDown();
    }

    public function testCreateBuildsContainerWithInitialHyperformBindings(): void
    {
        putenv('APP_ENV');
        putenv('APP_DEBUG');
        putenv('APP_CHARSET');
        putenv('APP_LOG_MAX_FILES');
        putenv('APP_LOG_LEVEL');
        putenv('APP_LOCALE');
        putenv('APP_FALLBACK_LOCALE');
        putenv('APP_NAME');
        putenv('APP_TIMEZONE');

        $databaseHost = getenv('DB_HOST') !== false ? (string) getenv('DB_HOST') : '127.0.0.1';
        $kafkaBrokers = getenv('KAFKA_BROKERS') !== false
            ? array_values(array_filter(array_map('trim', explode(',', (string) getenv('KAFKA_BROKERS')))))
            : ['127.0.0.1:9092'];
        $appName = getenv('APP_NAME') !== false ? (string) getenv('APP_NAME') : 'pix-withdrawal';
        $appEnvironment = getenv('APP_ENV') !== false ? (string) getenv('APP_ENV') : 'prod';
        $appDebug = getenv('APP_DEBUG') !== false
            ? (bool) filter_var((string) getenv('APP_DEBUG'), FILTER_VALIDATE_BOOL)
            : false;
        $appTimezone = getenv('APP_TIMEZONE') !== false ? (string) getenv('APP_TIMEZONE') : 'UTC';

        $container = (new HyperfContainerFactory())->create();
        $config = $container->get(ConfigInterface::class);

        self::assertSame($container, ApplicationContext::getContainer());
        self::assertTrue($container->has(ConfigInterface::class));
        self::assertTrue($container->has(ApplicationInterface::class));
        self::assertTrue($container->has(EventDispatcherInterface::class));
        self::assertTrue($container->has(LoggerInterface::class));
        self::assertTrue($container->has(StdoutLoggerInterface::class));
        self::assertTrue($container->has(DateTimeZone::class));
        self::assertTrue($container->has(AtomicAccountDebit::class));
        self::assertTrue($container->has(AccountScopedWithdrawQuery::class));
        self::assertTrue($container->has(Clock::class));
        self::assertTrue($container->has(ClockInterface::class));
        self::assertTrue($container->has(CorrelationIdGenerator::class));
        self::assertTrue($container->has(DomainEventDispatcher::class));
        self::assertTrue($container->has(FailureClassifier::class));
        self::assertTrue($container->has(SensitiveDataMasker::class));
        self::assertTrue($container->has(LogPayloadSerializer::class));
        self::assertTrue($container->has(EventPayloadSerializer::class));
        self::assertTrue($container->has(MetricEmitter::class));
        self::assertTrue($container->has(StructuredLogger::class));
        self::assertTrue($container->has(UuidGenerator::class));
        self::assertTrue($container->has(WithdrawDuplicateGuardFingerprintGenerator::class));
        self::assertTrue($container->has(WithdrawDuplicateGuardWindow::class));
        self::assertTrue($container->has(WithdrawIdempotencyKeyGenerator::class));
        self::assertTrue($container->has(Observability::class));
        self::assertTrue($container->has(MySqlConnectionConfig::class));
        self::assertTrue($container->has(MySqlConnectionConfigProvider::class));
        self::assertTrue($container->has(MySqlConnectionConfigValidator::class));
        self::assertTrue($container->has(DueScheduledWithdrawQuery::class));
        self::assertTrue($container->has(ProcessableWithdrawQuery::class));
        self::assertTrue($container->has(ScheduledWithdrawQueuePromotion::class));
        self::assertTrue($container->has(TransactionManager::class));
        self::assertTrue($container->has(WithdrawStatusViewQuery::class));
        self::assertTrue($container->has(SmtpWithdrawMailer::class));
        self::assertTrue($container->has(WithdrawNotificationDispatcher::class));
        self::assertTrue($container->has(WithdrawNotificationKafkaHandler::class));
        self::assertInstanceOf(ConfigInterface::class, $config);
        self::assertInstanceOf(Application::class, $container->get(ApplicationInterface::class));
        self::assertInstanceOf(EventDispatcherInterface::class, $container->get(EventDispatcherInterface::class));
        self::assertInstanceOf(LoggerInterface::class, $container->get(LoggerInterface::class));
        self::assertInstanceOf(StdoutLoggerInterface::class, $container->get(StdoutLoggerInterface::class));
        self::assertInstanceOf(DateTimeZone::class, $container->get(DateTimeZone::class));
        self::assertInstanceOf(AtomicAccountDebit::class, $container->get(AtomicAccountDebit::class));
        self::assertInstanceOf(DataAccountScopedWithdrawQuery::class, $container->get(AccountScopedWithdrawQuery::class));
        self::assertInstanceOf(Clock::class, $container->get(Clock::class));
        self::assertInstanceOf(ClockInterface::class, $container->get(ClockInterface::class));
        self::assertIsString($container->get(CorrelationIdGenerator::class)->generate());
        self::assertInstanceOf(KafkaDomainEventDispatcher::class, $container->get(DomainEventDispatcher::class));
        self::assertInstanceOf(FailureClassifier::class, $container->get(FailureClassifier::class));
        self::assertInstanceOf(SensitiveDataMasker::class, $container->get(SensitiveDataMasker::class));
        self::assertInstanceOf(LogPayloadSerializer::class, $container->get(LogPayloadSerializer::class));
        self::assertInstanceOf(EventPayloadSerializer::class, $container->get(EventPayloadSerializer::class));
        self::assertInstanceOf(MetricEmitter::class, $container->get(MetricEmitter::class));
        self::assertInstanceOf(StructuredLogger::class, $container->get(StructuredLogger::class));
        self::assertIsString($container->get(UuidGenerator::class)->generate());
        self::assertIsString(
            $container->get(WithdrawDuplicateGuardFingerprintGenerator::class)->generate(
                (new CreateWithdrawCommandValidator())->validate(
                    new \Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput(
                        'acc-1',
                        'corr-1',
                        'PIX',
                        'EMAIL',
                        'user@example.com',
                        '10.00'
                    ),
                    $container->get(Clock::class)
                )
            )
        );
        self::assertSame(60, $container->get(WithdrawDuplicateGuardWindow::class)->seconds());
        self::assertIsString(
            $container->get(WithdrawIdempotencyKeyGenerator::class)->generate(
                (new CreateWithdrawCommandValidator())->validate(
                    new \Tecnofit\PixWithdrawal\Core\Application\Command\CreateWithdrawInput(
                        'acc-1',
                        'corr-1',
                        'PIX',
                        'EMAIL',
                        'user@example.com',
                        '10.00'
                    ),
                    $container->get(Clock::class)
                )
            )
        );
        self::assertInstanceOf(Observability::class, $container->get(Observability::class));
        self::assertInstanceOf(HyperfObservability::class, $container->get(Observability::class));
        self::assertInstanceOf(MySqlConnectionConfig::class, $container->get(MySqlConnectionConfig::class));
        self::assertInstanceOf(MySqlConnectionConfigProvider::class, $container->get(MySqlConnectionConfigProvider::class));
        self::assertInstanceOf(MySqlConnectionConfigValidator::class, $container->get(MySqlConnectionConfigValidator::class));
        self::assertInstanceOf(DataDueScheduledWithdrawQuery::class, $container->get(DueScheduledWithdrawQuery::class));
        self::assertInstanceOf(DataProcessableWithdrawQuery::class, $container->get(ProcessableWithdrawQuery::class));
        self::assertInstanceOf(DataScheduledWithdrawQueuePromotion::class, $container->get(ScheduledWithdrawQueuePromotion::class));
        self::assertInstanceOf(MySqlTransactionManager::class, $container->get(TransactionManager::class));
        self::assertInstanceOf(DataWithdrawStatusViewQuery::class, $container->get(WithdrawStatusViewQuery::class));
        self::assertInstanceOf(SmtpWithdrawMailer::class, $container->get(SmtpWithdrawMailer::class));
        self::assertInstanceOf(WithdrawNotificationDispatcher::class, $container->get(WithdrawNotificationDispatcher::class));
        self::assertInstanceOf(WithdrawNotificationKafkaHandler::class, $container->get(WithdrawNotificationKafkaHandler::class));
        self::assertSame($container->get(Observability::class), $container->get(StructuredLogger::class));
        self::assertSame($container->get(Observability::class), $container->get(MetricEmitter::class));
        self::assertSame($appName, $config->get('app.name'));
        self::assertSame($config->get('providers.runtime.environment'), $config->get('app.env'));
        self::assertSame($appDebug, $config->get('app.debug'));
        self::assertSame('pt_BR', $config->get('app.locale'));
        self::assertSame('en', $config->get('app.fallback_locale'));
        self::assertSame('UTF-8', $config->get('app.charset'));
        self::assertSame($appTimezone, $config->get('app.timezone'));
        self::assertSame(60, $config->get('providers.identifiers.withdraw_duplicate_guard_window_seconds'));
        self::assertSame(
            [
                LogLevel::EMERGENCY,
                LogLevel::ALERT,
                LogLevel::CRITICAL,
                LogLevel::ERROR,
                LogLevel::WARNING,
                LogLevel::NOTICE,
                LogLevel::INFO,
            ],
            $config->get(StdoutLoggerInterface::class . '.log_level')
        );
        self::assertSame(
            RotatingFileHandler::class,
            $config->get('logger.default.handlers.0.class')
        );
        self::assertSame(
            BASE_PATH . '/runtime/logs/application/application.log',
            $config->get('logger.default.handlers.0.constructor.filename')
        );
        self::assertSame(14, $config->get('logger.default.handlers.0.constructor.maxFiles'));
        self::assertSame(Level::Info, $config->get('logger.default.handlers.0.constructor.level'));
        self::assertSame(
            [
                'default' => [
                    'driver' => 'mysql',
                    'host' => $databaseHost,
                    'port' => 3306,
                    'database' => 'pix_withdrawal',
                    'username' => 'pix_withdrawal',
                    'password' => 'pix_withdrawal',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                ],
                'default_connection' => 'default',
                'connections' => [
                    'default' => [
                        'driver' => 'mysql',
                        'host' => $databaseHost,
                        'port' => 3306,
                        'database' => 'pix_withdrawal',
                        'username' => 'pix_withdrawal',
                        'password' => 'pix_withdrawal',
                        'charset' => 'utf8mb4',
                        'collation' => 'utf8mb4_unicode_ci',
                        'prefix' => '',
                        'strict' => true,
                    ],
                ],
            ],
            $config->get('databases')
        );
        $kafkaConfig = $config->get('kafka');
        self::assertSame($kafkaBrokers, $kafkaConfig['brokers']);
        self::assertSame(
            sprintf('pix-withdrawal-%s', $config->get('providers.runtime.environment')),
            $kafkaConfig['client_id']
        );
        self::assertSame(
            sprintf('pix-withdrawal-%s-withdraw', $config->get('providers.runtime.environment')),
            $kafkaConfig['consumer_groups']['withdraw']['group_id']
        );
        self::assertSame(
            sprintf('pix-withdrawal-%s-notification', $config->get('providers.runtime.environment')),
            $kafkaConfig['consumer_groups']['notification']['group_id']
        );
        self::assertSame(1000, $kafkaConfig['operation_timeout_ms']);
        self::assertSame(5000, $kafkaConfig['flush_timeout_ms']);
        self::assertSame(
            $config->get('providers.runtime.environment') === 'local' ? 'earliest' : 'latest',
            $kafkaConfig['consumer_groups']['withdraw']['auto.offset.reset']
        );
        self::assertSame(
            $config->get('providers.runtime.environment') === 'local' ? 'earliest' : 'latest',
            $kafkaConfig['consumer_groups']['notification']['auto.offset.reset']
        );
        self::assertSame(
            $config->get('providers.runtime.environment') === 'local' ? '1' : 'all',
            $kafkaConfig['producers']['domain_events']['acks']
        );
        self::assertSame(
            [
                'withdraw_process',
                'withdraw_succeeded',
                'withdraw_failed',
                'notification_email_withdraw',
            ],
            $kafkaConfig['required_topic_keys']
        );
        self::assertSame(
            [
                'withdraw_process' => 'withdraw.process',
                'withdraw_succeeded' => 'withdraw.succeeded',
                'withdraw_failed' => 'withdraw.failed',
                'notification_email_withdraw' => 'notification.email.withdraw',
            ],
            $kafkaConfig['topics']
        );
        $mailConfig = $config->get('mail');
        self::assertSame('smtp', $mailConfig['default']);
        self::assertSame('smtp', $mailConfig['mailers']['smtp']['transport']);
        self::assertSame(
            getenv('MAIL_HOST') !== false ? (string) getenv('MAIL_HOST') : $mailConfig['mailers']['smtp']['host'],
            $mailConfig['mailers']['smtp']['host']
        );
        self::assertSame(
            getenv('MAIL_PORT') !== false ? (int) getenv('MAIL_PORT') : $mailConfig['mailers']['smtp']['port'],
            $mailConfig['mailers']['smtp']['port']
        );
        self::assertNull($mailConfig['mailers']['smtp']['username']);
        self::assertNull($mailConfig['mailers']['smtp']['password']);
        self::assertNull($mailConfig['mailers']['smtp']['encryption']);
        self::assertSame(
            getenv('MAIL_FROM_ADDRESS') !== false ? (string) getenv('MAIL_FROM_ADDRESS') : $mailConfig['mailers']['smtp']['from']['address'],
            $mailConfig['mailers']['smtp']['from']['address']
        );
        self::assertSame('PIX Withdrawal', $mailConfig['mailers']['smtp']['from']['name']);
        self::assertSame(5, $mailConfig['mailers']['smtp']['timeouts']['connect_seconds']);
        self::assertSame(5, $mailConfig['mailers']['smtp']['timeouts']['read_seconds']);
    }

    public function testDefaultLoggerWritesApplicationLogEntry(): void
    {
        $logFile = BASE_PATH . '/runtime/logs/application/application-' . date('Y-m-d') . '.log';

        if (file_exists($logFile)) {
            unlink($logFile);
        }

        $container = (new HyperfContainerFactory())->create();
        $logger = $container->get(LoggerInterface::class);

        $logger->warning('bootstrap smoke test', [
            'component' => 'bootstrap',
            'state' => 'ready',
        ]);

        self::assertFileExists($logFile);

        $logEntry = file_get_contents($logFile);

        self::assertIsString($logEntry);
        self::assertStringContainsString('bootstrap smoke test', $logEntry);
        self::assertStringContainsString('WARNING', $logEntry);
        self::assertStringContainsString('ready', $logEntry);
    }
}
