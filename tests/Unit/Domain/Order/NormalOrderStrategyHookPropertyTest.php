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

namespace HyperfTests\Unit\Domain\Order;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Strategy\NormalOrderStrategy;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use DG\BypassFinals;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

/**
 * Property 8: NormalOrderStrategy 钩子方法行为验证.
 *
 * - applyCoupon() 空列表不改变价格
 * - applyCoupon() 非空列表但优惠券不存在时抛异常
 *
 * @internal
 * @coversNothing
 */
final class NormalOrderStrategyHookPropertyTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ITERATIONS = 100;

    public static function setUpBeforeClass(): void
    {
        BypassFinals::enable();
    }

    /**
     * Property 8a: applyCoupon() with empty list sets couponAmount to 0 and does not change priceDetail.
     */
    public function testApplyCouponEmptyListDoesNotChangePriceDetail(): void
    {
        $strategy = $this->createStrategy();

        for ($i = 0; $i < self::ITERATIONS; ++$i) {
            $entity = $this->createEntityWithRandomPriceDetail();
            $before = $entity->getPriceDetail()->toArray();

            $strategy->applyCoupon($entity, null);

            self::assertSame(
                $before,
                $entity->getPriceDetail()->toArray(),
                "Iteration {$i}: applyCoupon([]) should not change priceDetail"
            );
            self::assertSame(
                0,
                $entity->getCouponAmount(),
                "Iteration {$i}: couponAmount should be 0 for empty list"
            );
        }
    }

    /**
     * Property 8c: applyCoupon() with non-existent coupon throws RuntimeException.
     */
    public function testApplyCouponWithInvalidCouponThrows(): void
    {
        $couponService = \Mockery::mock(CouponServiceInterface::class);
        $couponService->shouldReceive('findUsableCoupon')->andReturn(null);

        $strategy = new NormalOrderStrategy(
            \Mockery::mock(ProductSnapshotInterface::class),
            $couponService,
            \Mockery::mock(FreightServiceInterface::class),
        );

        $entity = $this->createEntityWithRandomPriceDetail();
        $couponId = random_int(1, 100000);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/不可用或已使用/');
        $strategy->applyCoupon($entity, $couponId);
    }

    private function createStrategy(): NormalOrderStrategy
    {
        return new NormalOrderStrategy(
            \Mockery::mock(ProductSnapshotInterface::class),
            \Mockery::mock(CouponServiceInterface::class),
            \Mockery::mock(FreightServiceInterface::class),
        );
    }

    private function createEntityWithRandomPriceDetail(): OrderEntity
    {
        $entity = new OrderEntity();
        $entity->setMemberId(random_int(1, 100000));
        $entity->setOrderType('normal');

        $priceDetail = new OrderPriceValue();
        $goodsAmount = random_int(1, 999999);
        $discountAmount = random_int(0, $goodsAmount);
        $shippingFee = random_int(0, 10000);

        $priceDetail->setGoodsAmount($goodsAmount);
        $priceDetail->setDiscountAmount($discountAmount);
        $priceDetail->setShippingFee($shippingFee);
        $entity->setPriceDetail($priceDetail);

        return $entity;
    }
}
