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

namespace App\Domain\Trade\Order\Strategy;

use App\Domain\Marketing\Coupon\Repository\CouponUserRepository;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;

final class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly CouponUserRepository $couponUserRepository,
    ) {}

    public function type(): string
    {
        return 'normal';
    }

    /**
     * 验证订单.
     */
    public function validate(OrderEntity $orderEntity): void
    {
        if ($orderEntity->getMemberId() <= 0) {
            throw new \RuntimeException('请先登录后再下单');
        }
        if (empty($orderEntity->getItems())) {
            throw new \RuntimeException('至少选择一件商品');
        }
    }

    /**
     * 构建订单.
     */
    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $itemPayloads = $orderEntity->getItems();
        $skuIds = array_map(static fn (OrderItemEntity $item) => $item->getSkuId(), $itemPayloads);
        $skuIds = array_filter(array_unique($skuIds));
        if ($skuIds === []) {
            throw new \RuntimeException('商品信息不完整');
        }

        $snapshots = $this->snapshotService->getSkuSnapshots($skuIds);

        foreach ($itemPayloads as $item) {
            $item->ensureQuantityPositive();

            $snapshot = $snapshots[$item->getSkuId()] ?? null;
            if (! $snapshot) {
                throw new \RuntimeException(\sprintf('SKU %d 不存在或已下架', $item->getSkuId()));
            }

            $skuStatus = (string) ($snapshot['sku_status'] ?? '');
            if ($skuStatus !== ProductSku::STATUS_ACTIVE) {
                throw new \RuntimeException(\sprintf('商品 %s 已下架', $item->getSkuName()));
            }

            $productStatus = (string) ($snapshot['product_status'] ?? '');
            if ($productStatus !== Product::STATUS_ACTIVE) {
                $productName = (string) ($snapshot['product_name'] ?? $item->getSkuName());
                throw new \RuntimeException(\sprintf('商品 %s 已禁用', $productName));
            }

            $item->attachSnapshot($snapshot);
        }

        $orderEntity->syncPriceDetailFromItems();

        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setDiscountAmount(0);
        $priceDetail->setShippingFee(0);
        $orderEntity->setPriceDetail($priceDetail);

        return $orderEntity;
    }

    /**
     * 应用优惠券：验证归属/状态/有效期/满减门槛，计算折扣写入 Entity.
     *
     * @param array<int, array{coupon_id: int}> $couponList
     */
    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void
    {
        if (empty($couponList)) {
            $orderEntity->setCouponAmount(0);
            return;
        }

        $couponIds = array_filter(array_map(
            static fn (array $item) => (int) ($item['coupon_id'] ?? 0),
            $couponList,
        ));
        $couponIds = array_unique($couponIds);

        if (empty($couponIds)) {
            $orderEntity->setCouponAmount(0);
            return;
        }

        // 查找该会员未使用且未过期的 coupon_user 记录
        $couponUserMap = $this->couponUserRepository->findUnusedByMemberAndCouponIds(
            $orderEntity->getMemberId(),
            $couponIds,
        );

        $goodsAmount = $orderEntity->getPriceDetail()?->getGoodsAmount() ?? 0;
        $totalDiscount = 0;
        $appliedCouponUserIds = [];

        foreach ($couponIds as $couponId) {
            $couponUser = $couponUserMap[$couponId] ?? null;
            if (! $couponUser) {
                throw new \RuntimeException(\sprintf('优惠券 %d 不可用或已使用', $couponId));
            }

            $coupon = $couponUser->coupon;
            if (! $coupon || $coupon->status !== 'active') {
                throw new \RuntimeException(\sprintf('优惠券 %d 已失效', $couponId));
            }

            // 满减门槛检查（分）
            $minAmount = (int) $coupon->min_amount;
            if ($minAmount > 0 && $goodsAmount < $minAmount) {
                throw new \RuntimeException(\sprintf(
                    '优惠券 %s 需满 %d 分可用，当前商品金额 %d 分',
                    $coupon->name,
                    $minAmount,
                    $goodsAmount,
                ));
            }

            // 计算折扣金额（分）
            $discount = $this->calculateCouponDiscount($coupon, $goodsAmount);
            $totalDiscount += $discount;
            $appliedCouponUserIds[] = (int) $couponUser->id;
        }

        // 折扣不能超过商品总额
        if ($totalDiscount > $goodsAmount) {
            $totalDiscount = $goodsAmount;
        }

        $orderEntity->setCouponAmount($totalDiscount);
        $orderEntity->setAppliedCouponUserIds($appliedCouponUserIds);

        // 将优惠券金额写入 priceDetail 的 discountAmount
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $currentDiscount = $priceDetail->getDiscountAmount();
        $priceDetail->setDiscountAmount($currentDiscount + $totalDiscount);
        $orderEntity->setPriceDetail($priceDetail);
    }

    /**
     * 调整价格（普通订单默认不做额外价格调整，直通）.
     */
    public function adjustPrice(OrderEntity $orderEntity): void
    {
        // 普通订单不做额外价格调整
    }

    public function postCreate(OrderEntity $orderEntity): void
    {
        // 普通订单暂不需要特殊后置逻辑
    }

    /**
     * 根据优惠券类型计算折扣金额（分）.
     */
    private function calculateCouponDiscount(object $coupon, int $goodsAmount): int
    {
        $value = (int) ($coupon->value ?? 0);
        $type = (string) ($coupon->type ?? 'fixed');

        return match ($type) {
            // percent/discount: value=850 表示 8.5 折，折扣 = 商品金额 - 折后金额
            'percent', 'discount' => $goodsAmount - (int) round($goodsAmount * $value / 1000),
            // fixed: 面值（分）直接减
            default => $value,
        };
    }
}
