<?php

declare(strict_types=1);

namespace HyperfTests\Unit\PluginExtraction;

use App\Domain\Catalog\Product\Event\ProductStockWarningEvent;
use App\Domain\Catalog\Product\Listener\ProductStockWarningListener;
use App\Domain\Infrastructure\Plugin\Contract\SystemNotificationInterface;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Event\OrderCancelledEvent;
use App\Domain\Trade\Order\Listener\OrderStatusNotifyListener;
use App\Infrastructure\Plugin\NullSystemNotification;
use App\Infrastructure\Service\Trade\NullTradeWebhook;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OptionalNotificationsTest extends TestCase
{
    public function testOrderAndStockEventsRetainNotificationBehaviorAndWorkWithPluginDisabled(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting('', [], ['system' => true], '', '', '', false));
        $order = $this->createMock(OrderEntity::class);
        $order->method('getMemberId')->willReturn(7);
        $order->method('getOrderNo')->willReturn('ORDER-7');
        $notifications = $this->createMock(SystemNotificationInterface::class);
        $notifications->expects(self::once())->method('notify')->with('订单 ORDER-7 已取消', '取消原因：取消测试');
        $notifications->expects(self::once())->method('broadcast')->with('SKU 3 库存预警', 'SKU 3 当前库存：2，已达到预警阈值 5，请及时补货。');
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::exactly(2))->method('warning');
        foreach ([$notifications, new NullSystemNotification()] as $gateway) {
            (new OrderStatusNotifyListener($settings, new NullTradeWebhook(), $gateway))->process(new OrderCancelledEvent($order, '取消测试'));
            (new ProductStockWarningListener($settings, $logger, $gateway))->process(new ProductStockWarningEvent(3, 2, 5));
        }
    }
}
