<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;
use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;

final class DomainExternalFulfillmentService
{
    public function __construct(private readonly ExternalFulfillmentInterface $capability, private readonly ProductSnapshotInterface $products) {}

    public function prepare(OrderEntity $order): void
    {
        $items = $order->getItems();
        $external = array_filter($items, static fn ($item) => $item->getFulfillmentProvider() !== 'merchant');
        if ($external === []) {
            return;
        }
        if ($order->getOrderType() !== 'normal') {
            throw new \DomainException('外部履约商品暂不支持活动库存下单');
        }
        $snapshots = $this->products->getSkuSnapshots(array_map(static fn ($item) => $item->getSkuId(), $external));
        $payload = [];
        foreach ($external as $item) {
            $snapshot = $snapshots[$item->getSkuId()] ?? null;
            if ($snapshot === null || ($snapshot['fulfillment_provider'] ?? '') !== $item->getFulfillmentProvider()) {
                throw new \DomainException('商品履约信息已变化，请重新结算');
            }
            $payload[] = $snapshot + ['quantity' => $item->getQuantity()];
        }
        $quote = $this->capability->prepare($payload, $order->getAddress()?->toArray() ?? [], $order->getOrderType());
        if (! isset($quote['shipping_fee']) || ! is_int($quote['shipping_fee']) || $quote['shipping_fee'] < 0) {
            throw new \DomainException('履约报价运费无效');
        }
        foreach ($external as $item) {
            $result = $quote['items'][$item->getSkuId()] ?? null;
            if (! is_array($result) || ($result['provider'] ?? '') !== $item->getFulfillmentProvider() || empty($result['snapshot'])) {
                throw new \DomainException('履约服务未返回完整商品快照');
            }
            $item->freezeFulfillment($result['provider'], $result['snapshot']);
        }
        $price = $order->getPriceDetail();
        if ($price === null) {
            throw new \DomainException('订单金额尚未构建');
        }
        $price->setShippingFee($price->getShippingFee() + $quote['shipping_fee']);
        $order->setPriceDetail($price);
    }
}
