<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace HyperfTests\Unit\Domain\Trade\AfterSale\Service;

use App\Domain\Trade\AfterSale\Contract\AfterSaleApplyInput;
use App\Domain\Trade\AfterSale\Entity\AfterSaleEntity;
use App\Domain\Trade\AfterSale\Enum\AfterSaleStatus;
use App\Domain\Trade\AfterSale\Enum\AfterSaleType;
use App\Domain\Trade\AfterSale\Repository\AfterSaleRepository;
use App\Domain\Trade\AfterSale\Service\DomainAfterSaleService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Domain\Trade\Order\Repository\OrderRepository;
use App\Infrastructure\Model\AfterSale\AfterSale;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;
use Carbon\Carbon;
use DG\BypassFinals;
use DomainException;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class DomainAfterSaleServiceTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    public function testEligibilityForPaidOrderAllowsRefundOnly(): void
    {
        [$service] = $this->makeService(orderStatus: OrderStatus::PAID->value);

        $eligibility = $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);

        self::assertTrue($eligibility['can_apply']);
        self::assertSame(['refund_only'], $eligibility['types']);
        self::assertSame(1, $eligibility['max_quantity']);
        self::assertSame(18800, $eligibility['max_amount']);
    }

    public function testEligibilityForShippedOrderAllowsAllTypes(): void
    {
        [$service] = $this->makeService(orderStatus: OrderStatus::SHIPPED->value);

        $eligibility = $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);

        self::assertTrue($eligibility['can_apply']);
        self::assertSame(['refund_only', 'return_refund', 'exchange'], $eligibility['types']);
    }

    public function testEligibilityRejectsPendingOrder(): void
    {
        [$service] = $this->makeService(orderStatus: OrderStatus::PENDING->value);

        $this->expectException(DomainException::class);
        $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);
    }

    public function testEligibilityRejectsWhenActiveAfterSaleExists(): void
    {
        $activeAfterSale = new class extends AfterSale {
            public function __construct() {}
        };
        $activeAfterSale->setRawAttributes(['id' => 1], true);

        [$service] = $this->makeService(
            orderStatus: OrderStatus::SHIPPED->value,
            existingAfterSale: $activeAfterSale,
        );

        $this->expectException(DomainException::class);
        $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);
    }

    public function testEligibilityRejectsCompletedOrderOutsideAfterSaleWindow(): void
    {
        [$service] = $this->makeService(
            orderStatus: OrderStatus::COMPLETED->value,
            orderUpdatedAt: Carbon::now()->subDays(16),
            afterSaleDays: 15,
        );

        $this->expectException(DomainException::class);
        $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);
    }

    public function testEligibilityAllowsCompletedOrderWithinAfterSaleWindow(): void
    {
        [$service] = $this->makeService(
            orderStatus: OrderStatus::COMPLETED->value,
            orderUpdatedAt: Carbon::now()->subDays(14),
            afterSaleDays: 15,
        );

        $eligibility = $service->eligibility(memberId: 3003, orderId: 1001, orderItemId: 2002);

        self::assertTrue($eligibility['can_apply']);
    }

    public function testApplyPersistsAfterSaleEntity(): void
    {
        [$service, $orderRepository, $afterSaleRepository] = $this->makeService(orderStatus: OrderStatus::COMPLETED->value);
        $input = $this->makeInput(type: AfterSaleType::RETURN_REFUND);

        $orderRepository
            ->expects(self::once())
            ->method('findOrderItemForAfterSale')
            ->with(3003, 1001, 2002)
            ->willReturn($this->makeOrderItem(OrderStatus::COMPLETED->value));

        $afterSaleRepository
            ->expects(self::once())
            ->method('findActiveByOrderItemId')
            ->with(2002)
            ->willReturn(null);

        $afterSaleRepository
            ->expects(self::once())
            ->method('createFromEntity')
            ->with(self::callback(static function (AfterSaleEntity $entity): bool {
                return $entity->getType() === AfterSaleType::RETURN_REFUND->value
                    && $entity->getStatus() === AfterSaleStatus::PENDING_REVIEW->value
                    && $entity->getOrderId() === 1001
                    && $entity->getOrderItemId() === 2002;
            }))
            ->willReturnCallback(static function (AfterSaleEntity $entity): AfterSale {
                $entity->setId(99);
                return new class extends AfterSale {
                    public function __construct() {}
                };
            });

        $entity = $service->apply($input);

        self::assertSame(99, $entity->getId());
        self::assertSame(AfterSaleType::RETURN_REFUND->value, $entity->getType());
    }

    /**
     * @return array{0: DomainAfterSaleService, 1: OrderRepository, 2: AfterSaleRepository}
     */
    private function makeService(
        string $orderStatus,
        ?AfterSale $existingAfterSale = null,
        ?Carbon $orderUpdatedAt = null,
        int $afterSaleDays = 15,
    ): array
    {
        $orderRepository = $this->createMock(OrderRepository::class);
        $afterSaleRepository = $this->createMock(AfterSaleRepository::class);
        $mallSettingService = $this->createMock(DomainMallSettingService::class);

        $orderRepository
            ->method('findOrderItemForAfterSale')
            ->willReturn($this->makeOrderItem($orderStatus, $orderUpdatedAt));

        $afterSaleRepository
            ->method('findActiveByOrderItemId')
            ->willReturn($existingAfterSale);

        $mallSettingService
            ->method('order')
            ->willReturn(new OrderSetting(30, 7, $afterSaleDays, true, 'system', '400-888-1000'));

        return [new DomainAfterSaleService($orderRepository, $afterSaleRepository, $mallSettingService), $orderRepository, $afterSaleRepository];
    }

    private function makeOrderItem(string $orderStatus, ?Carbon $orderUpdatedAt = null): OrderItem
    {
        $order = new class extends Order {
            public function __construct() {}
        };
        $order->setRawAttributes([
            'id' => 1001,
            'member_id' => 3003,
            'status' => $orderStatus,
            'updated_at' => $orderUpdatedAt ?? Carbon::now(),
        ], true);

        $item = new class extends OrderItem {
            public function __construct() {}
        };
        $item->setRawAttributes([
            'id' => 2002,
            'order_id' => 1001,
            'quantity' => 1,
            'total_price' => 18800,
        ], true);
        $item->setRelation('order', $order);

        return $item;
    }

    private function makeInput(AfterSaleType $type = AfterSaleType::REFUND_ONLY): AfterSaleApplyInput
    {
        return new class($type) implements AfterSaleApplyInput {
            public function __construct(private readonly AfterSaleType $type)
            {
            }

            public function getOrderId(): int
            {
                return 1001;
            }

            public function getOrderItemId(): int
            {
                return 2002;
            }

            public function getMemberId(): int
            {
                return 3003;
            }

            public function getType(): string
            {
                return $this->type->value;
            }

            public function getReason(): string
            {
                return 'Damaged package';
            }

            public function getDescription(): ?string
            {
                return 'Outer box broken';
            }

            public function getApplyAmount(): int
            {
                return 18800;
            }

            public function getQuantity(): int
            {
                return 1;
            }

            public function getImages(): ?array
            {
                return ['https://img.example/2.png'];
            }
        };
    }
}
