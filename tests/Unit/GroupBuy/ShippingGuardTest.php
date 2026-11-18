<?php

declare(strict_types=1);

namespace HyperfTests\Unit\GroupBuy;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use App\Domain\Trade\GroupBuy\Repository\GroupBuyOrderRepository;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Infrastructure\Model\GroupBuy\GroupBuyOrder;
use PHPUnit\Framework\TestCase;

final class ShippingGuardTest extends TestCase
{
    public function testPaidButUngroupedOrderCannotShipUntilGroupSucceeds(): void
    {
        $status = 'paid';
        $row = $this->createMock(GroupBuyOrder::class);
        $row->method('__get')->willReturnCallback(static function (string $key) use (&$status) {
            return $key === 'status' ? $status : null;
        });
        $groups = $this->createMock(GroupBuyOrderRepository::class);
        $groups->method('findByOrderId')->with(1)->willReturn($row);
        $orders = $this->createMock(OrderRepository::class);
        $orders->expects(self::once())->method('ship');
        $settings = $this->createMock(DomainMallSettingService::class);
        $settings->expects(self::once())->method('shipping')->willReturn($this->createMock(ShippingSetting::class));
        $entity = $this->createMock(OrderEntity::class);
        $entity->method('getId')->willReturn(1);
        $entity->method('getOrderType')->willReturn('group_buy');
        $entity->expects(self::once())->method('ensureShippable');
        $service = new DomainOrderService($orders, $settings, $groups);
        try {
            $service->ship($entity);
            self::fail('ungrouped order shipped');
        } catch (\DomainException $error) {
            self::assertSame('拼团尚未成功，不能发货', $error->getMessage());
        }
        $status = 'grouped';
        self::assertSame($entity, $service->ship($entity));
    }
}
