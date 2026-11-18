<?php

declare(strict_types=1);

namespace HyperfTests\Integration\SystemMessage;

use App\Infrastructure\Model\Permission\User;
use Hyperf\Config\Config;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Logger\LoggerFactory;
use HyperfTests\Support\DatabaseTestCase;
use Plugin\Sms\Model\SmsMessage;
use Plugin\Sms\Service\SmsSenderInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Contract\NotificationTransportInterface;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\NotificationFailed;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Event\NotificationSent;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Repository\UserPreferenceRepository;
use Plugin\SystemMessage\Domain\Infrastructure\SystemMessage\Service\NotificationService;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\Message;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\MessageDeliveryLog;
use Plugin\SystemMessage\Infrastructure\Model\SystemMessage\UserMessage;
use Plugin\Wechat\Interfaces\MiniAppInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;

final class NotificationChannelTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->migrate('plugins/system-message/Database/Migrations/2024_12_21_000005_create_message_delivery_logs_table.php');
        $this->migrate('plugins/system-message/Database/Migrations/2024_12_21_000002_create_user_messages_table.php');
        $this->container->set(ConfigInterface::class, new Config(['system_message' => ['notification' => ['default_channels' => array_fill_keys(['database', 'websocket', 'socketio', 'email', 'sms', 'push', 'miniapp'], true)]]]));
        $logger = $this->createMock(LoggerFactory::class);
        $logger->method('make')->willReturn(new NullLogger());
        $this->container->set(LoggerFactory::class, $logger);
    }

    public function testGatewayAcceptanceIsSentAndFailureIsRecordedWithoutSentEvent(): void
    {
        $transport = $this->createMock(NotificationTransportInterface::class);
        $transport->expects(self::exactly(2))->method('gateway')->willReturnCallback(static function (string $channel, int $userId, array $payload): void {
            self::assertSame(7, $userId);
            self::assertSame(11, $payload['message_id']);
            if ($channel === 'push') {
                throw new \RuntimeException('gateway rejected');
            }
        });
        $events = [];
        $dispatcher = $this->createMock(EventDispatcherInterface::class);
        $dispatcher->method('dispatch')->willReturnCallback(static function (object $event) use (&$events): object {
            if ($event instanceof NotificationSent || $event instanceof NotificationFailed) {
                $events[] = $event;
            } return $event;
        });
        $this->container->set(EventDispatcherInterface::class, $dispatcher);
        $service = $this->service($transport);
        self::assertTrue($service->send($this->message(), 7, 'websocket'));
        $this->assertDeliveryFails($service, $this->message(), 7, 'push');
        $logs = $this->connection->table('message_delivery_logs')->orderBy('id')->get();
        self::assertSame('sent', $logs[0]->status);
        self::assertNull($logs[0]->delivered_at);
        self::assertSame('failed', $logs[1]->status);
        self::assertSame('gateway rejected', $logs[1]->error_message);
        self::assertNull($logs[1]->sent_at);
        self::assertInstanceOf(NotificationSent::class, $events[0]);
        self::assertInstanceOf(NotificationFailed::class, $events[1]);
        self::assertSame(['total' => 2, 'successful' => 1, 'failed' => 1, 'pending' => 0, 'success_rate' => 50.0], MessageDeliveryLog::getDeliveryStats(11));
        self::assertSame(1, MessageDeliveryLog::getChannelStats()['push']['failed']);
    }

    public function testDatabaseDeliveryRequiresActualUndeletedReceipt(): void
    {
        $service = $this->service($this->createMock(NotificationTransportInterface::class));
        UserMessage::query()->create(['user_id' => 7, 'message_id' => 11]);
        self::assertTrue($service->send($this->message(), 7, 'database'));
        $this->assertDeliveryFails($service, $this->message(), 8, 'database');
        self::assertSame('delivered', $this->connection->table('message_delivery_logs')->where('user_id', 7)->value('status'));
        self::assertSame('failed', $this->connection->table('message_delivery_logs')->where('user_id', 8)->value('status'));
    }

    public function testEmailRendersEscapedContentAndSmsPreservesAbsentTemplateCode(): void
    {
        $transport = $this->createMock(NotificationTransportInterface::class);
        $transport->expects(self::once())->method('email')->with('user@example.test', '<订单>', self::callback(static fn (string $html): bool => str_contains($html, '&lt;订单&gt;') && ! str_contains($html, '<script>')));
        $sms = $this->createMock(SmsSenderInterface::class);
        $sms->expects(self::once())->method('send')->with(self::callback(static fn (SmsMessage $message): bool => $message->phone() === '13800138000' && $message->templateCode() === null));
        $service = $this->service($transport, $sms);
        self::assertTrue($service->send($this->message(), 7, 'email'));
        self::assertTrue($service->send($this->message(), 7, 'sms'));
        self::assertSame(2, $this->connection->table('message_delivery_logs')->where('status', 'sent')->count());
    }

    public function testMiniappRequiresSuccessfulProviderReceiptAndSingleRecipient(): void
    {
        $miniapp = $this->createMock(MiniAppInterface::class);
        $miniapp->expects(self::exactly(4))->method('sendSubscribeMessage')->willReturnOnConsecutiveCalls(['errcode' => 43101], [], ['errcode' => 'invalid'], ['errcode' => 0]);
        $service = $this->service($this->createMock(NotificationTransportInterface::class), null, $miniapp);
        $message = $this->message();
        $message->extra_data = ['openid' => 'fixture-openid', 'miniapp_template_id' => 'fixture-template'];
        $this->assertDeliveryFails($service, $message, 7, 'miniapp');
        $this->assertDeliveryFails($service, $message, 7, 'miniapp');
        $this->assertDeliveryFails($service, $message, 7, 'miniapp');
        self::assertTrue($service->send($message, 7, 'miniapp'));
        $message->recipient_ids = [7, 8];
        $this->assertDeliveryFails($service, $message, 8, 'miniapp');
        self::assertSame(4, $this->connection->table('message_delivery_logs')->where('status', 'failed')->count());
    }

    private function assertDeliveryFails(NotificationService $service, Message $message, int $userId, string $channel): void
    {
        try {
            $service->send($message, $userId, $channel);
            self::fail('Delivery failure must reach the queue retry handler');
        } catch (\RuntimeException|\DomainException $error) {
            self::assertNotSame('', $error->getMessage());
        }
    }

    private function service(NotificationTransportInterface $transport, ?SmsSenderInterface $sms = null, ?MiniAppInterface $miniapp = null): NotificationService
    {
        $preferences = $this->createMock(UserPreferenceRepository::class);
        $preferences->method('getUserPreference')->willReturn(null);
        $service = $this->getMockBuilder(NotificationService::class)->setConstructorArgs([$preferences, $sms ?? $this->createMock(SmsSenderInterface::class), $miniapp ?? $this->createMock(MiniAppInterface::class), $transport])->onlyMethods(['getUserById'])->getMock();
        $service->method('getUserById')->willReturn(new User(['email' => 'user@example.test', 'phone' => '13800138000']));
        return $service;
    }

    private function message(): Message
    {
        $message = new Message(['title' => '<订单>', 'content' => '<script>alert(1)</script>待处理', 'type' => 'system', 'priority' => 1, 'recipient_type' => 'user', 'recipient_ids' => [7]]);
        $message->id = 11;
        return $message;
    }
}
