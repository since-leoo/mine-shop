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

namespace HyperfTests\Feature\Domain\Order;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Marketing\GroupBuy\Entity\GroupBuyEntity;
use App\Domain\Marketing\GroupBuy\Service\DomainGroupBuyOrderService;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Strategy\GroupBuyOrderStrategy;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Feature: group-buy-order, Properties 2, 7, 8, 9: GroupBuyOrderStrategy 属性测试.
 *
 * - Property 2: 基本输入验证拒绝
 * - Property 7: 团购价格计算正确性
 * - Property 8: 拼团订单拒绝优惠券
 * - Property 9: adjustPrice 幂等性
 *
 * Validates: Requirements 4.1, 4.2, 5.2, 5.3, 5.4, 5.5, 6.1, 6.2
 *
 * @internal
 * @coversNothing
 */
final class GroupBuyOrderStrategyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    // =========================================================================
    // Property 2: 基本输入验证拒绝
    // **Validates: Requirements 4.1, 4.2**
    // =========================================================================

    /**
     * Property 2: 基本输入验证拒绝 — memberId <= 0.
     *
     * For any OrderEntity with memberId <= 0, validate() should throw RuntimeException.
     *
     * **Validates: Requirements 4.1**
     *
     * @dataProvider provideProperty2_MemberIdLessThanOrEqualZeroIsRejectedCases
     */
    public function testProperty2MemberIdLessThanOrEqualZeroIsRejected(int $memberId): void
    {
        $strategy = $this->makeStrategy();

        $orderEntity = new OrderEntity();
        $orderEntity->setMemberId($memberId);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('请先登录后再下单');

        $strategy->validate($orderEntity);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideProperty2_MemberIdLessThanOrEqualZeroIsRejectedCases(): iterable
    {
        // 50 iterations with memberId = 0
        for ($i = 0; $i < 50; ++$i) {
            yield "zero_{$i}" => [0];
        }
        // 50 iterations with memberId < 0
        for ($i = 0; $i < 50; ++$i) {
            yield "negative_{$i}" => [-random_int(1, 999_999)];
        }
    }

    /**
     * Property 2: 基本输入验证拒绝 — 商品列表为空.
     *
     * For any OrderEntity with empty items, validate() should throw RuntimeException.
     *
     * **Validates: Requirements 4.2**
     */
    public function testProperty2EmptyItemsIsRejected(): void
    {
        $strategy = $this->makeStrategy();

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $orderEntity = new OrderEntity();
            $orderEntity->setMemberId(random_int(1, 999_999));
            // No items added — items array is empty

            $threw = false;
            try {
                $strategy->validate($orderEntity);
            } catch (\RuntimeException $e) {
                self::assertSame('至少选择一件商品', $e->getMessage(), "iteration {$i}");
                $threw = true;
            }
            self::assertTrue($threw, "iteration {$i}: should have thrown RuntimeException for empty items");
        }
    }

    /**
     * Property 2: 基本输入验证拒绝 — 多个 SKU.
     *
     * For any OrderEntity with more than one SKU, validate() should throw RuntimeException.
     *
     * **Validates: Requirements 4.2**
     *
     * @dataProvider provideProperty2_MultipleSkuIsRejectedCases
     */
    public function testProperty2MultipleSkuIsRejected(int $itemCount): void
    {
        $strategy = $this->makeStrategy();

        $orderEntity = new OrderEntity();
        $orderEntity->setMemberId(random_int(1, 999_999));

        for ($j = 0; $j < $itemCount; ++$j) {
            $item = new OrderItemEntity();
            $item->setSkuId(random_int(1, 999_999));
            $item->setQuantity(random_int(1, 10));
            $orderEntity->addItem($item);
        }

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('拼团订单仅支持单个商品');

        $strategy->validate($orderEntity);
    }

    /**
     * @return iterable<string, array{int}>
     */
    public static function provideProperty2_MultipleSkuIsRejectedCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $itemCount = random_int(2, 10);
            yield "iteration_{$i} (items={$itemCount})" => [$itemCount];
        }
    }

    // =========================================================================
    // Property 7: 团购价格计算正确性
    // **Validates: Requirements 5.2, 5.3, 5.4, 5.5**
    // =========================================================================

    /**
     * Property 7: 团购价格计算正确性.
     *
     * For any valid GroupBuyEntity (groupPrice) and order item (quantity),
     * after buildDraft():
     * - item.unitPrice equals groupPrice
     * - item.totalPrice equals groupPrice * quantity
     * - goodsAmount equals totalPrice
     * - shippingFee equals 0
     * - discountAmount equals 0
     *
     * **Validates: Requirements 5.2, 5.3, 5.4, 5.5**
     *
     * @dataProvider provideProperty7_GroupBuyPriceCalculationCorrectnessCases
     */
    public function testProperty7GroupBuyPriceCalculationCorrectness(int $groupPrice, int $quantity, int $skuId): void
    {
        $snapshot = [
            'product_id' => random_int(1, 999),
            'product_name' => '测试商品',
            'sku_name' => '默认规格',
            'sale_price' => random_int(10000, 99999), // original price, will be overridden
            'weight' => 0.5,
        ];

        $snapshotService = \Mockery::mock(ProductSnapshotInterface::class);
        $snapshotService->shouldReceive('getSkuSnapshots')
            ->with([$skuId])
            ->andReturn([$skuId => $snapshot]);

        $groupBuyOrderService = \Mockery::mock(DomainGroupBuyOrderService::class);

        $strategy = new GroupBuyOrderStrategy($snapshotService, $groupBuyOrderService);

        // Build the order entity with a single item
        $item = new OrderItemEntity();
        $item->setSkuId($skuId);
        $item->setQuantity($quantity);

        $orderEntity = new OrderEntity();
        $orderEntity->setMemberId(random_int(1, 999_999));
        $orderEntity->setOrderType('group_buy');
        $orderEntity->setItems($item);

        // Set up GroupBuyEntity in extras (as validate() would do)
        $groupBuyEntity = new GroupBuyEntity();
        $groupBuyEntity->setGroupPrice($groupPrice);
        $orderEntity->setExtra('group_buy_entity', $groupBuyEntity);

        // Execute buildDraft
        $result = $strategy->buildDraft($orderEntity);

        // Verify item prices
        $resultItem = $result->getItems()[0];
        self::assertSame($groupPrice, $resultItem->getUnitPrice(), 'item.unitPrice should equal groupPrice');
        self::assertSame($groupPrice * $quantity, $resultItem->getTotalPrice(), 'item.totalPrice should equal groupPrice * quantity');

        // Verify order-level prices
        $priceDetail = $result->getPriceDetail();
        self::assertNotNull($priceDetail, 'priceDetail should not be null');
        self::assertSame($groupPrice * $quantity, $priceDetail->getGoodsAmount(), 'goodsAmount should equal groupPrice * quantity');
        self::assertSame(0, $priceDetail->getShippingFee(), 'shippingFee should be 0');
        self::assertSame(0, $priceDetail->getDiscountAmount(), 'discountAmount should be 0');

        // Also verify the synced fields on OrderEntity
        self::assertSame($groupPrice * $quantity, $result->getGoodsAmount(), 'OrderEntity.goodsAmount should equal groupPrice * quantity');
        self::assertSame(0, $result->getShippingFee(), 'OrderEntity.shippingFee should be 0');
        self::assertSame(0, $result->getDiscountAmount(), 'OrderEntity.discountAmount should be 0');

        \Mockery::close();
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideProperty7_GroupBuyPriceCalculationCorrectnessCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $groupPrice = random_int(1, 99999);
            $quantity = random_int(1, 20);
            $skuId = random_int(1, 999_999);
            yield "iteration_{$i} (price={$groupPrice}, qty={$quantity})" => [$groupPrice, $quantity, $skuId];
        }
    }

    // =========================================================================
    // Property 8: 拼团订单拒绝优惠券
    // **Validates: Requirements 6.1**
    // =========================================================================

    /**
     * Property 8: 拼团订单拒绝优惠券.
     *
     * For any non-empty coupon list, applyCoupon() should throw RuntimeException.
     *
     * **Validates: Requirements 6.1**
     *
     * @dataProvider provideProperty8_GroupBuyOrderRejectsCouponsCases
     */
    public function testProperty8GroupBuyOrderRejectsCoupons(array $couponList): void
    {
        self::assertNotEmpty($couponList, 'Precondition: couponList should not be empty');

        $strategy = $this->makeStrategy();
        $orderEntity = new OrderEntity();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('拼团订单不支持使用优惠券');

        $strategy->applyCoupon($orderEntity, $couponList);
    }

    /**
     * @return iterable<string, array{array<int, mixed>}>
     */
    public static function provideProperty8_GroupBuyOrderRejectsCouponsCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $couponCount = random_int(1, 5);
            $coupons = [];
            for ($j = 0; $j < $couponCount; ++$j) {
                $coupons[] = [
                    'coupon_id' => random_int(1, 999_999),
                    'coupon_user_id' => random_int(1, 999_999),
                    'discount' => random_int(100, 5000),
                ];
            }
            yield "iteration_{$i} (count={$couponCount})" => [$coupons];
        }
    }

    // =========================================================================
    // Property 9: adjustPrice 幂等性
    // **Validates: Requirements 6.2**
    // =========================================================================

    /**
     * Property 9: adjustPrice 幂等性.
     *
     * For any OrderEntity, calling adjustPrice() should not change any price fields
     * (goodsAmount, shippingFee, discountAmount, totalAmount, payAmount).
     *
     * **Validates: Requirements 6.2**
     *
     * @dataProvider provideProperty9_AdjustPriceIdempotencyCases
     */
    public function testProperty9AdjustPriceIdempotency(
        int $goodsAmount,
        int $shippingFee,
        int $discountAmount,
    ): void {
        $strategy = $this->makeStrategy();

        $orderEntity = new OrderEntity();
        $orderEntity->setMemberId(random_int(1, 999_999));

        // Set up price detail
        $priceDetail = new OrderPriceValue();
        $priceDetail->setGoodsAmount($goodsAmount);
        $priceDetail->setShippingFee($shippingFee);
        $priceDetail->setDiscountAmount($discountAmount);
        $orderEntity->setPriceDetail($priceDetail);

        // Capture prices before adjustPrice
        $beforeGoodsAmount = $orderEntity->getGoodsAmount();
        $beforeShippingFee = $orderEntity->getShippingFee();
        $beforeDiscountAmount = $orderEntity->getDiscountAmount();
        $beforeTotalAmount = $orderEntity->getTotalAmount();
        $beforePayAmount = $orderEntity->getPayAmount();

        // Call adjustPrice
        $strategy->adjustPrice($orderEntity);

        // Verify all prices remain unchanged
        self::assertSame($beforeGoodsAmount, $orderEntity->getGoodsAmount(), 'goodsAmount should not change');
        self::assertSame($beforeShippingFee, $orderEntity->getShippingFee(), 'shippingFee should not change');
        self::assertSame($beforeDiscountAmount, $orderEntity->getDiscountAmount(), 'discountAmount should not change');
        self::assertSame($beforeTotalAmount, $orderEntity->getTotalAmount(), 'totalAmount should not change');
        self::assertSame($beforePayAmount, $orderEntity->getPayAmount(), 'payAmount should not change');
    }

    /**
     * @return iterable<string, array{int, int, int}>
     */
    public static function provideProperty9_AdjustPriceIdempotencyCases(): iterable
    {
        for ($i = 0; $i < 100; ++$i) {
            $goodsAmount = random_int(100, 999_999);
            $shippingFee = random_int(0, 5000);
            $discountAmount = random_int(0, min($goodsAmount, 50000));
            yield "iteration_{$i} (goods={$goodsAmount}, ship={$shippingFee}, disc={$discountAmount})" => [
                $goodsAmount, $shippingFee, $discountAmount,
            ];
        }
    }

    // =========================================================================
    // Shared helpers
    // =========================================================================

    /**
     * Creates a GroupBuyOrderStrategy with mocked dependencies (for tests that don't need specific mock behavior).
     */
    private function makeStrategy(): GroupBuyOrderStrategy
    {
        $snapshotService = \Mockery::mock(ProductSnapshotInterface::class);
        $groupBuyOrderService = \Mockery::mock(DomainGroupBuyOrderService::class);

        return new GroupBuyOrderStrategy($snapshotService, $groupBuyOrderService);
    }
}
