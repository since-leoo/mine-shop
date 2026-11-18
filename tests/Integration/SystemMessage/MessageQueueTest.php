<?php

declare(strict_types=1);

namespace HyperfTests\Integration\SystemMessage;

use Hyperf\AsyncQueue\Driver\DriverFactory;
use Hyperf\AsyncQueue\Driver\DriverInterface;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\Sms\Service\SmsSenderInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\MessageSendFailed;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\MessageSent;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Job\SendMessageJob;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\MessageRepository;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\UserPreferenceRepository;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\MessageService;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\NotificationService;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class MessageQueueTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (new \Mine\Support\Listener\RegisterBlueprintListener())->process(new \stdClass());
        require_once BASE_PATH . '/migrations/2021_04_12_160526_create_user_table.php';
        (new \CreateUserTable())->up();
        foreach (['000001_create_system_messages', '000002_create_user_messages', '000005_create_message_delivery_logs'] as $file) {
            $this->migrate('plugins/system-message/Database/Migrations/2024_12_21_' . $file . '_table.php');
        }
        foreach ([7, 8] as $id) {
            $this->connection->table('user')->insert(['id' => $id, 'username' => 'notify-' . $id, 'password' => 'fixture', 'status' => 1]);
        }
        $this->container->set(ConfigInterface::class, new Config(['system_message' => ['notification' => ['default_channels' => ['database' => true, 'push' => true]]]]));
        $logger = $this->createMock(LoggerFactory::class);
        $logger->method('make')->willReturn(new NullLogger());
        $this->container->set(LoggerFactory::class, $logger);
        $this->container->set(LoggerInterface::class, new NullLogger());
    }

    public function testPartialQueueRejectionIsFailedAndRetryDoesNotRedeliverSuccessfulRecipient(): void
    {
        $transport = $this->createMock(NotificationTransportInterface::class);
        $transport->expects(self::exactly(2))->method('gateway');
        $notification = $this->notifications($transport);
        $this->container->set(NotificationService::class, $notification);
        $events = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event) use (&$events): object {
            if ($event instanceof MessageSent || $event instanceof MessageSendFailed) {
                $events[] = $event;
            }
            return $event;
        });
        $this->container->set(EventDispatcherInterface::class, $dispatcher);
        $calls = 0;
        $driver = $this->createMock(DriverInterface::class);
        $driver->expects(self::exactly(3))->method('push')->willReturnCallback(function (SendMessageJob $job) use (&$calls): bool {
            self::assertSame(0, $this->connection->transactionLevel(), 'Queue push must follow receipt commit');
            ++$calls;
            if ($calls === 2) {
                return false;
            }
            $job->handle();
            $job->handle();
            return true;
        });
        $factory = $this->createMock(DriverFactory::class);
        $factory->method('get')->willReturn($driver);
        $service = new MessageService(new MessageRepository(), $notification, $factory);
        $message = $this->message();
        try {
            $service->send($message->id);
            self::fail('A rejected queue push must fail');
        } catch (\RuntimeException $error) {
            self::assertStringContainsString('通知队列拒绝', $error->getMessage());
        }
        self::assertSame('failed', $message->fresh()->status);
        self::assertNull($message->fresh()->sent_at);
        self::assertSame(1, $this->connection->table('message_delivery_logs')->where('status', 'sent')->count());
        self::assertInstanceOf(MessageSendFailed::class, $events[0]);
        self::assertTrue($service->send($message->id));
        self::assertSame('sent', $message->fresh()->status);
        self::assertSame(2, $this->connection->table('user_messages')->count());
        self::assertSame(2, $this->connection->table('message_delivery_logs')->where('status', 'sent')->count());
        self::assertInstanceOf(MessageSent::class, $events[1]);
    }

    public function testChannelFailureIsRecordedAndRetriedWhileCompletedJobIsIdempotent(): void
    {
        $message = $this->message();
        $this->connection->table('user_messages')->insert(['message_id' => $message->id, 'user_id' => 7]);
        $attempt = 0;
        $transport = $this->createMock(NotificationTransportInterface::class);
        $transport->expects(self::exactly(2))->method('gateway')->willReturnCallback(static function () use (&$attempt): void {
            if (++$attempt === 1) {
                throw new \RuntimeException('channel unavailable');
            }
        });
        $this->container->set(NotificationService::class, $this->notifications($transport));
        $job = new SendMessageJob($message->id, 7, 'push');
        try {
            $job->handle();
            self::fail('Transport failure must trigger queue retry');
        } catch (\RuntimeException $error) {
            self::assertSame('channel unavailable', $error->getMessage());
        }
        self::assertSame(1, $this->connection->table('message_delivery_logs')->where('status', 'failed')->count());
        $job->handle();
        $job->handle();
        self::assertSame(1, $this->connection->table('message_delivery_logs')->where('status', 'sent')->count());
    }

    public function testMissingUncommittedMessageIsRetriedInsteadOfSilentlyDiscarded(): void
    {
        $this->container->set(NotificationService::class, $this->notifications($this->createMock(NotificationTransportInterface::class)));
        $this->expectException(\RuntimeException::class);
        (new SendMessageJob(999, 7, 'push'))->handle();
    }

    public function testRealAsyncQueueAopPreservesBooleanResultAndSingleDelivery(): void
    {
        $message = $this->message(['database']);
        $this->connection->table('user_messages')->insert(['message_id' => $message->id, 'user_id' => 7]);
        $process = proc_open([PHP_BINARY, __DIR__ . '/notification-aop-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), (string) $message->id], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
        self::assertIsResource($process);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        self::assertSame(0, proc_close($process), $stderr . $stdout);
        self::assertSame(['sent' => true, 'deliveries' => 1, 'async_aspect_active' => true], json_decode(trim($stdout), true, 512, JSON_THROW_ON_ERROR));
    }

    public function testConcurrentDuplicateJobsShareOneSuccessfulDelivery(): void
    {
        $message = $this->message(['database']);
        $this->connection->table('user_messages')->insert(['message_id' => $message->id, 'user_id' => 7]);
        $this->connection->beginTransaction();
        $this->connection->table('user_messages')->where('message_id', $message->id)->lockForUpdate()->first();
        $workers = [];
        for ($i = 0; $i < 2; ++$i) {
            $process = proc_open([PHP_BINARY, __DIR__ . '/notification-aop-worker.php', (string) getenv('MINE_SHOP_TEST_MYSQL_SOCKET'), $this->connection->getDatabaseName(), (string) $message->id, 'concurrent'], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, BASE_PATH);
            self::assertIsResource($process);
            fclose($pipes[0]);
            $workers[] = [$process, $pipes];
        }
        foreach ($workers as [, $pipes]) {
            self::assertSame("ready\n", fgets($pipes[1]));
        }
        $this->connection->commit();
        foreach ($workers as [$process, $pipes]) {
            $stdout = stream_get_contents($pipes[1]);
            fclose($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[2]);
            self::assertSame(0, proc_close($process), $stderr . $stdout);
        }
        self::assertSame(1, $this->connection->table('message_delivery_logs')->where('status', 'delivered')->count());
    }

    private function message(array $channels = ['push']): Message
    {
        return Message::query()->create(['title' => '通知测试', 'content' => 'fixture', 'type' => 'system', 'priority' => 1, 'recipient_type' => 'user', 'recipient_ids' => [7, 8], 'channels' => $channels, 'status' => 'draft']);
    }

    private function notifications(NotificationTransportInterface $transport): NotificationService
    {
        $preferences = $this->createMock(UserPreferenceRepository::class);
        $preferences->method('getUserPreference')->willReturn(null);
        return new NotificationService($preferences, $this->createMock(SmsSenderInterface::class), $this->createMock(MiniAppInterface::class), $transport);
    }
}
