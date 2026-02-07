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

use App\Domain\Marketing\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Order\ValueObject\OrderAddressValue;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class NormalOrderStrategyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testBuildDraftSuccess(): void
    {
        $strategy = $this->makeStrategy([
            1 => $this->makeSnapshot([
                'id' => 1,
                'product_id' => 9,
                'sku_name' => '标准款',
                'sale_price' => 10000,
                'weight' => 1.5,
            ]),
        ]);

        $entity = $this->makeOrderEntity();
        $draft = $strategy->buildDraft($entity);

        self::assertSame('normal', $draft->getOrderType());
        self::assertSame(1, \count($draft->getItems()));
        $item = $draft->getItems()[0];
        self::assertSame(1, $item->getSkuId());
        self::assertSame(2, $item->getQuantity());
        self::assertSame(20000, $item->getTotalPrice());
        self::assertSame(20000, $draft->getPriceDetail()?->getGoodsAmount());
        self::assertSame(0, $draft->getPriceDetail()?->getShippingFee());
    }

    public function testValidateRequiresAddress(): void
    {
        $strategy = $this->makeStrategy([]);
        $entity = $this->makeOrderEntity([
            'address' => ['name' => '', 'phone' => '', 'detail' => ''],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('请完善收货地址信息');
        $strategy->validate($entity);
    }

    public function testBuildDraftFailsWhenSkuInactive(): void
    {
        $strategy = $this->makeStrategy([
            1 => $this->makeSnapshot([
                'id' => 1,
                'sku_name' => '标准款',
                'status' => ProductSku::STATUS_INACTIVE,
            ]),
        ]);
        $entity = $this->makeOrderEntity([
            'items' => [
                ['sku_id' => 1, 'quantity' => 5, 'unit_price' => 10000, 'sku_name' => '标准款'],
            ],
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('商品 标准款 已下架');
        $strategy->buildDraft($entity);
    }

    public function testApplyCouponEmptyList(): void
    {
        $strategy = $this->makeStrategy([]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $strategy->applyCoupon($entity, []);

        self::assertSame(0, $entity->getCouponAmount());
    }

    public function testApplyCouponFixedDiscount(): void
    {
        $couponUser = $this->makeCouponUser(1, 'fixed', 1000, 5000);
        $strategy = $this->makeStrategy([], [1 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $strategy->applyCoupon($entity, [['coupon_id' => 1]]);

        // fixed: value=1000 分 (¥10), 直接减
        self::assertSame(1000, $entity->getCouponAmount());
        self::assertSame(1000, $entity->getPriceDetail()->getDiscountAmount());
    }

    public function testApplyCouponPercentDiscount(): void
    {
        // value=850 表示 8.5 折, goodsAmount=10000 分
        // discount = 10000 - (int) round(10000 * 850 / 1000) = 10000 - 8500 = 1500
        $couponUser = $this->makeCouponUser(2, 'percent', 850, 0);
        $strategy = $this->makeStrategy([], [2 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $strategy->applyCoupon($entity, [['coupon_id' => 2]]);

        self::assertSame(1500, $entity->getCouponAmount());
    }

    public function testApplyCouponDiscountType(): void
    {
        // 'discount' type 与 'percent' 相同逻辑
        // value=900 表示 9 折, goodsAmount=20000 分
        // discount = 20000 - (int) round(20000 * 900 / 1000) = 20000 - 18000 = 2000
        $couponUser = $this->makeCouponUser(3, 'discount', 900, 0);
        $strategy = $this->makeStrategy([], [3 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(20000);

        $strategy->applyCoupon($entity, [['coupon_id' => 3]]);

        self::assertSame(2000, $entity->getCouponAmount());
    }

    public function testApplyCouponMinAmountThreshold(): void
    {
        // min_amount=15000 分 (¥150), goodsAmount=10000 分 (¥100) → 不满足门槛
        $couponUser = $this->makeCouponUser(1, 'fixed', 1000, 15000);
        $strategy = $this->makeStrategy([], [1 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('需满');
        $strategy->applyCoupon($entity, [['coupon_id' => 1]]);
    }

    public function testApplyCouponDiscountCappedAtGoodsAmount(): void
    {
        // fixed value=20000 分 but goodsAmount=10000 分 → capped at 10000
        $couponUser = $this->makeCouponUser(1, 'fixed', 20000, 0);
        $strategy = $this->makeStrategy([], [1 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $strategy->applyCoupon($entity, [['coupon_id' => 1]]);

        self::assertSame(10000, $entity->getCouponAmount());
    }

    public function testApplyCouponThrowsWhenCouponNotFound(): void
    {
        $strategy = $this->makeStrategy([], []);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('优惠券 99 不可用或已使用');
        $strategy->applyCoupon($entity, [['coupon_id' => 99]]);
    }

    public function testApplyCouponThrowsWhenCouponInactive(): void
    {
        $couponUser = $this->makeCouponUser(1, 'fixed', 1000, 0, 'inactive');
        $strategy = $this->makeStrategy([], [1 => $couponUser]);
        $entity = $this->makeOrderEntityWithPrice(10000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('优惠券 1 已失效');
        $strategy->applyCoupon($entity, [['coupon_id' => 1]]);
    }

    private function makeOrderEntityWithPrice(int $goodsAmount): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->setMemberId(1);
        $entity->setOrderType('normal');

        $priceDetail = new OrderPriceValue();
        $priceDetail->setGoodsAmount($goodsAmount);
        $entity->setPriceDetail($priceDetail);

        return $entity;
    }

    /**
     * @return object{id: int, coupon_id: int, coupon: object}
     */
    private function makeCouponUser(int $couponId, string $type, int $value, int $minAmount, string $couponStatus = 'active'): object
    {
        $coupon = new \stdClass();
        $coupon->name = '测试优惠券';
        $coupon->type = $type;
        $coupon->value = $value;
        $coupon->min_amount = $minAmount;
        $coupon->status = $couponStatus;

        $couponUser = new \stdClass();
        $couponUser->id = $couponId * 10;
        $couponUser->coupon_id = $couponId;
        $couponUser->coupon = $coupon;

        return $couponUser;
    }

    private function makeOrderEntity(array $overrides = []): OrderEntity
    {
        $defaults = [
            'member_id' => 1,
            'order_type' => 'normal',
            'items' => [
                [
                    'sku_id' => 1,
                    'quantity' => 2,
                    'unit_price' => 10000,
                    'product_name' => '测试商品',
                    'sku_name' => '标准款',
                ],
            ],
            'address' => [
                'name' => '张三',
                'phone' => '13800138000',
                'province' => '广东',
                'city' => '广州',
                'district' => '天河',
                'detail' => '体育西路',
            ],
            'remark' => '请尽快发货',
        ];

        $data = array_merge($defaults, $overrides);

        $entity = new OrderEntity();
        $entity->setMemberId($data['member_id']);
        $entity->setOrderType($data['order_type']);
        foreach ($data['items'] as $itemPayload) {
            $entity->setItems($this->toOrderItem($itemPayload));
        }
        $entity->setAddress(OrderAddressValue::fromArray($data['address']));
        $entity->setBuyerRemark($data['remark']);

        return $entity;
    }

    /**
     * @param array<string, mixed> $itemPayload
     */
    private function toOrderItem(array $itemPayload): OrderItemEntity
    {
        $item = new OrderItemEntity();
        $item->setSkuId((int) ($itemPayload['sku_id'] ?? 0));
        $item->setProductId((int) ($itemPayload['product_id'] ?? 0));
        $item->setProductName((string) ($itemPayload['product_name'] ?? '测试商品'));
        $item->setSkuName((string) ($itemPayload['sku_name'] ?? '默认规格'));
        $item->setUnitPrice((int) ($itemPayload['unit_price'] ?? 0));
        $item->setQuantity((int) ($itemPayload['quantity'] ?? 0));

        return $item;
    }

    /**
     * @param array<string, mixed> $attributes
     * @return array<string, mixed>
     */
    private function makeSnapshot(array $attributes): array
    {
        $skuId = $attributes['id'] ?? 1;
        return [
            'product_id' => $attributes['product_id'] ?? 1,
            'product_name' => $attributes['product_name'] ?? '测试商品',
            'product_status' => $attributes['product_status'] ?? Product::STATUS_ACTIVE,
            'product_image' => $attributes['product_image'] ?? null,
            'sku_id' => $skuId,
            'sku_name' => $attributes['sku_name'] ?? '默认规格',
            'sku_status' => $attributes['status'] ?? ProductSku::STATUS_ACTIVE,
            'sku_image' => $attributes['image'] ?? null,
            'spec_values' => $attributes['spec_values'] ?? [],
            'sale_price' => (int) ($attributes['sale_price'] ?? 9900),
            'weight' => (float) ($attributes['weight'] ?? 1.0),
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     * @param array<int, object> $couponUserMap coupon_id => couponUser mock object
     */
    private function makeStrategy(array $snapshots, array $couponUserMap = []): NormalOrderStrategy
    {
        $snapshotService = \Mockery::mock(ProductSnapshotInterface::class);
        $snapshotService->shouldReceive('getSkuSnapshots')
            ->andReturnUsing(static function (array $ids) use ($snapshots): array {
                $result = [];
                foreach ($ids as $id) {
                    if (isset($snapshots[$id])) {
                        $result[$id] = $snapshots[$id];
                    }
                }
                return $result;
            });

        $couponUserRepo = \Mockery::mock(CouponUserRepository::class);
        $couponUserRepo->shouldReceive('findUnusedByMemberAndCouponIds')
            ->andReturnUsing(static function (int $memberId, array $couponIds) use ($couponUserMap): array {
                $result = [];
                foreach ($couponIds as $id) {
                    if (isset($couponUserMap[$id])) {
                        $result[$id] = $couponUserMap[$id];
                    }
                }
                return $result;
            });

        return new NormalOrderStrategy($snapshotService, $couponUserRepo);
    }
}
