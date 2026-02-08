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

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Trade\Order\Contract\CouponServiceInterface;
use App\Domain\Trade\Order\Contract\FreightServiceInterface;
use App\Domain\Trade\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Domain\Trade\Order\Entity\OrderItemEntity;
use App\Domain\Trade\Order\ValueObject\OrderPriceValue;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use Psr\Container\ContainerInterface;

final class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService,
        private readonly CouponServiceInterface $couponServiceInterface,
        private readonly FreightServiceInterface $freightServiceInterface,
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
     */
    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void
    {
        if ($couponId === null || $couponId <= 0) {
            $orderEntity->setCouponAmount(0);
            return;
        }

        $couponData = $this->couponServiceInterface->findUsableCoupon($orderEntity->getMemberId(), $couponId);
        if (! $couponData) {
            throw new \RuntimeException(\sprintf('优惠券 %d 不可用或已使用', $couponId));
        }

        if ($couponData['status'] !== 'active') {
            throw new \RuntimeException(\sprintf('优惠券 %d 已失效', $couponId));
        }

        $goodsAmount = $orderEntity->getPriceDetail()?->getGoodsAmount() ?? 0;

        // 满减门槛检查（分）
        $minAmount = $couponData['min_amount'];
        if ($minAmount > 0 && $goodsAmount < $minAmount) {
            throw new \RuntimeException(\sprintf(
                '优惠券 %s 需满 %d 分可用，当前商品金额 %d 分',
                $couponData['name'],
                $minAmount,
                $goodsAmount,
            ));
        }

        // 计算折扣金额（分）
        $discount = $this->calculateDiscount($couponData['type'], $couponData['value'], $goodsAmount);

        // 折扣不能超过商品总额
        if ($discount > $goodsAmount) {
            $discount = $goodsAmount;
        }

        $orderEntity->setCouponAmount($discount);
        $orderEntity->setAppliedCouponUserIds([$couponData['id']]);

        // 将优惠券金额写入 priceDetail 的 discountAmount
        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $currentDiscount = $priceDetail->getDiscountAmount();
        $priceDetail->setDiscountAmount($currentDiscount + $discount);
        $orderEntity->setPriceDetail($priceDetail);
    }

    /**
     * 计算并设置订单运费.
     */
    public function applyFreight(OrderEntity $orderEntity): void
    {
        $province = $orderEntity->getAddress()?->getProvince() ?? '';
        $totalFreight = $this->freightServiceInterface->calculateForItems($orderEntity->getItems(), $province);

        $priceDetail = $orderEntity->getPriceDetail() ?? new OrderPriceValue();
        $priceDetail->setShippingFee($totalFreight);
        $orderEntity->setPriceDetail($priceDetail);
    }

    /**
     * 从快照重建活动实体（普通订单无需恢复）.
     */
    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void {}

    /**
     * 订单创建后处理：核销优惠券.
     */
    public function postCreate(OrderEntity $orderEntity): void
    {
        $couponUserIds = $orderEntity->getAppliedCouponUserIds();
        foreach ($couponUserIds as $couponUserId) {
            $this->couponServiceInterface->settleCoupon($couponUserId, $orderEntity->getId());
        }
    }

    /**
     * 根据优惠券类型计算折扣金额（分）.
     */
    private function calculateDiscount(string $type, int $value, int $goodsAmount): int
    {
        return match ($type) {
            'percent', 'discount' => $goodsAmount - (int) round($goodsAmount * $value / 1000),
            default => $value,
        };
    }
}
