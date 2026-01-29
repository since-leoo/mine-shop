<?php

declare(strict_types=1);

namespace App\Domain\Order\Strategy;

use App\Domain\Order\Contract\OrderTypeStrategyInterface;
use App\Domain\Order\Entity\OrderDraftEntity;
use App\Domain\Order\Entity\OrderDraftItemEntity;
use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Entity\OrderSubmitCommand;
use App\Domain\Order\ValueObject\OrderAddressValue;
use App\Domain\Order\ValueObject\OrderPriceValue;
use App\Domain\Product\Contract\ProductSnapshotInterface;
use App\Infrastructure\Model\Product\Product;
use App\Infrastructure\Model\Product\ProductSku;
use RuntimeException;

final class NormalOrderStrategy implements OrderTypeStrategyInterface
{
    public function __construct(
        private readonly ProductSnapshotInterface $snapshotService
    ) {}

    public function type(): string
    {
        return 'normal';
    }

    public function validate(OrderSubmitCommand $command): void
    {
        if ($command->getMemberId() <= 0) {
            throw new RuntimeException('请先登录后再下单');
        }
        if (empty($command->getItems())) {
            throw new RuntimeException('至少选择一件商品');
        }

        $address = $command->getAddress();
        if (empty($address['name']) || empty($address['phone']) || empty($address['detail'])) {
            throw new RuntimeException('请完善收货地址信息');
        }
    }

    public function buildDraft(OrderSubmitCommand $command): OrderDraftEntity
    {
        $itemPayloads = $command->getItems();
        $skuIds = array_map(static fn (array $item) => (int) ($item['sku_id'] ?? 0), $itemPayloads);
        $skuIds = array_filter(array_unique($skuIds));
        if ($skuIds === []) {
            throw new RuntimeException('商品信息不完整');
        }

        $snapshots = $this->snapshotService->getSkuSnapshots($skuIds);

        $draft = new OrderDraftEntity();
        $draft->setOrderType($command->getOrderType());
        $draft->setMemberId($command->getMemberId());
        $draft->setBuyerRemark($command->getBuyerRemark());

        $goodsAmount = 0.0;
        foreach ($itemPayloads as $payload) {
            $skuId = (int) ($payload['sku_id'] ?? 0);
            $quantity = (int) ($payload['quantity'] ?? 0);
            if ($skuId <= 0 || $quantity <= 0) {
                throw new RuntimeException('商品数量必须大于0');
            }

            $snapshot = $snapshots[$skuId] ?? null;
            if (! $snapshot) {
                throw new RuntimeException(sprintf('SKU %d 不存在或已下架', $skuId));
            }

            $skuStatus = (string) ($snapshot['sku_status'] ?? '');
            $skuName = (string) ($snapshot['sku_name'] ?? $skuId);
            if ($skuStatus !== ProductSku::STATUS_ACTIVE) {
                throw new RuntimeException(sprintf('商品 %s 已下架', $skuName));
            }

            $productStatus = (string) ($snapshot['product_status'] ?? '');
            if ($productStatus !== Product::STATUS_ACTIVE) {
                $productName = (string) ($snapshot['product_name'] ?? $skuName);
                throw new RuntimeException(sprintf('商品 %s 已禁用', $productName));
            }

            $itemEntity = new OrderDraftItemEntity();
            $itemEntity->setProductId((int) ($snapshot['product_id'] ?? 0));
            $itemEntity->setSkuId($skuId);
            $itemEntity->setProductName((string) ($snapshot['product_name'] ?? ''));
            $itemEntity->setSkuName($skuName);
            $itemEntity->setProductImage($snapshot['sku_image'] ?? $snapshot['product_image'] ?? null);
            $itemEntity->setSpecValues((array) ($snapshot['spec_values'] ?? []));
            $itemEntity->setUnitPrice((float) ($snapshot['sale_price'] ?? 0));
            $itemEntity->setQuantity($quantity);
            $itemEntity->setWeight((float) ($snapshot['weight'] ?? 0));
            $draft->addItem($itemEntity);

            $goodsAmount += $itemEntity->getTotalPrice();
        }

        $address = OrderAddressValue::fromArray($command->getAddress());
        $draft->setAddress($address);

        $priceDetail = new OrderPriceValue();
        $priceDetail->setGoodsAmount($goodsAmount);
        $priceDetail->setDiscountAmount(0.0);
        $priceDetail->setShippingFee(0.0);
        $draft->setPriceDetail($priceDetail);

        return $draft;
    }

    public function postCreate(OrderEntity $order, OrderDraftEntity $draft): void
    {
        // 普通订单暂不需要特殊后置逻辑
    }
}
