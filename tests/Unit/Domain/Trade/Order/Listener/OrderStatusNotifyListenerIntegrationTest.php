<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Order\Listener;

use App\Domain\Infrastructure\SystemMessage\Service\OutboundWebhookDispatcher;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\IntegrationSetting;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\Event\OrderShippedEvent;
use App\Domain\Trade\Order\Listener\OrderStatusNotifyListener;
use DG\BypassFinals;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class OrderStatusNotifyListenerIntegrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testProcessDispatchesWebhookForShippedOrder(): void
    {
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->method('integration')->willReturn(new IntegrationSetting(
            'aliyun',
            [],
            ['system' => false],
            '',
            '',
            'https://hooks.example/orders',
            false
        ));

        $webhook = $this->createMock(OutboundWebhookDispatcher::class);
        $webhook->expects(self::once())
            ->method('dispatch')
            ->with('order.shipped', self::callback(static function (array $payload): bool {
                return $payload['order_no'] === 'ORD1001'
                    && $payload['member_id'] === 3003
                    && $payload['shipping_company'] === 'SF'
                    && $payload['shipping_no'] === 'SF1001';
            }))
            ->willReturn(true);

        $listener = new OrderStatusNotifyListener($settings, $webhook);
        $listener->process(new OrderShippedEvent($this->makeOrder(), $this->makeShipment()));
    }

    private function makeOrder(): OrderEntity
    {
        $order = new OrderEntity();
        $order->setOrderNo('ORD1001');
        $order->setMemberId(3003);
        $order->setPayAmount(18800);

        return $order;
    }

    private function makeShipment(): OrderShipEntity
    {
        $shipment = new OrderShipEntity();
        $shipment->setPackages([[
            'shipping_company' => 'SF',
            'shipping_no' => 'SF1001',
        ]]);

        return $shipment;
    }
}
