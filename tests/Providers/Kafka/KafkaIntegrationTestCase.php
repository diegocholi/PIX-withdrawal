<?php

declare(strict_types=1);

namespace Tecnofit\PixWithdrawal\Tests\Providers\Kafka;

use PHPUnit\Framework\TestCase;
use RdKafka\Conf;
use RdKafka\KafkaConsumer;
use RdKafka\Message;
use RdKafka\Producer;
use Tecnofit\PixWithdrawal\Core\Shared\Contract\StructuredLogger;
use Tecnofit\PixWithdrawal\Core\Shared\LogContext;
use Tecnofit\PixWithdrawal\Providers\Kafka\KafkaConsumerMessage;
use Tecnofit\PixWithdrawal\Providers\Kafka\RdkafkaConsumer;

abstract class KafkaIntegrationTestCase extends TestCase
{
    protected function uniqueTopic(string $prefix): string
    {
        return sprintf('%s.%s', $prefix, bin2hex(random_bytes(8)));
    }

    protected function uniqueGroupId(string $prefix): string
    {
        return sprintf('%s-%s', $prefix, bin2hex(random_bytes(8)));
    }

    protected function brokers(): string
    {
        $brokers = getenv('KAFKA_BROKERS');

        return is_string($brokers) && trim($brokers) !== '' ? trim($brokers) : 'kafka:19092';
    }

    protected function consumeKafkaMessage(string $topic, string $groupId, int $timeoutMs = 10000): ?KafkaConsumerMessage
    {
        $consumer = new RdkafkaConsumer(new KafkaConsumer($this->consumerConf($groupId)));
        $consumer->subscribe([$topic]);

        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $message = $consumer->consume(250);

            if ($message !== null) {
                return $message;
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    protected function rawKafkaMessage(string $topic, string $groupId, int $timeoutMs = 4000): ?Message
    {
        $consumer = new KafkaConsumer($this->consumerConf($groupId));
        $consumer->subscribe([$topic]);

        $deadline = microtime(true) + ($timeoutMs / 1000);

        do {
            $message = $consumer->consume(250);

            if ($message->err === RD_KAFKA_RESP_ERR_NO_ERROR) {
                return $message;
            }

            if (! in_array($message->err, [RD_KAFKA_RESP_ERR__TIMED_OUT, RD_KAFKA_RESP_ERR__PARTITION_EOF], true)) {
                self::fail(sprintf(
                    'Kafka consumer polling failed for topic "%s" with code %d: %s',
                    $topic,
                    $message->err,
                    $message->errstr(),
                ));
            }
        } while (microtime(true) < $deadline);

        return null;
    }

    protected function publishRawMessage(
        string $topic,
        string $payload,
        ?string $key = null,
        array $headers = [],
    ): void {
        $producer = new Producer($this->producerConf());
        $topicProducer = $producer->newTopic($topic);
        $topicProducer->producev(RD_KAFKA_PARTITION_UA, 0, $payload, $key, $headers);
        $producer->poll(0);

        $flushResult = $producer->flush(5000);

        self::assertSame(
            RD_KAFKA_RESP_ERR_NO_ERROR,
            $flushResult,
            sprintf('Kafka raw publish flush failed with code %d.', $flushResult),
        );
    }

    protected function nullStructuredLogger(): StructuredLogger
    {
        return new class implements StructuredLogger {
            public function emergency(string $message, LogContext $context): void
            {
            }

            public function alert(string $message, LogContext $context): void
            {
            }

            public function critical(string $message, LogContext $context): void
            {
            }

            public function error(string $message, LogContext $context): void
            {
            }

            public function warning(string $message, LogContext $context): void
            {
            }

            public function notice(string $message, LogContext $context): void
            {
            }

            public function info(string $message, LogContext $context): void
            {
            }

            public function debug(string $message, LogContext $context): void
            {
            }
        };
    }

    private function producerConf(): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', 'pix-withdrawal-integration');
        $conf->set('bootstrap.servers', $this->brokers());

        return $conf;
    }

    private function consumerConf(string $groupId): Conf
    {
        $conf = new Conf();
        $conf->set('client.id', 'pix-withdrawal-integration');
        $conf->set('bootstrap.servers', $this->brokers());
        $conf->set('group.id', $groupId);
        $conf->set('enable.auto.commit', 'false');
        $conf->set('auto.offset.reset', 'earliest');

        return $conf;
    }
}
