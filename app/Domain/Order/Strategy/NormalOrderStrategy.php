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

namespace App\Domain\Order\Strategy;

use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\ValueObject\OrderPriceValue;
use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;

final class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService
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

        $address = $orderEntity->getAddress();
        if (empty($address['name']) || empty($address['phone']) || empty($address['detail'])) {
            throw new \RuntimeException('请完善收货地址信息');
        }
    }

    /**
     * 构建订单.
     */
    public function buildDraft(OrderEntity $orderEntity): OrderEntity
    {
        $itemPayloads = $orderEntity->getItems();
        $skuIds = array_map(static fn (array $item) => (int) ($item['sku_id'] ?? 0), $itemPayloads);
        $skuIds = array_filter(array_unique($skuIds));
        if ($skuIds === []) {
            throw new \RuntimeException('商品信息不完整');
        }

        $snapshots = $this->snapshotService->getSkuSnapshots($skuIds);

        $goodsAmount = 0;
        foreach ($itemPayloads as $item) {
            if ($item->getQuantity() <= 0) {
                throw new \RuntimeException('商品数量必须大于0');
            }

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

            $goodsAmount = (float) bcadd((string) $item->getTotalPrice(), (string) $goodsAmount, 2);
        }

        $priceDetail = new OrderPriceValue();
        $priceDetail->setGoodsAmount($goodsAmount);
        $priceDetail->setDiscountAmount(0.0);
        $priceDetail->setShippingFee(0.0);
        $orderEntity->setPriceDetail($priceDetail);

        return $orderEntity;
    }

    public function postCreate(OrderEntity $orderEntity): void
    {
        // 普通订单暂不需要特殊后置逻辑
    }
}
