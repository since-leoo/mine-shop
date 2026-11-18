<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Service;

use App\Domain\Catalog\Product\Contract\ProductSnapshotInterface;

use App\Domain\Infrastructure\Plugin\Contract\ExternalFulfillmentInterface;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Infrastructure\Error\CommerceError;

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
            throw CommerceError::PROMOTION_FULFILLMENT_UNSUPPORTED->exception();
        }
        $snapshots = $this->products->getSkuSnapshots(array_map(static fn ($item) => $item->getSkuId(), $external));
        $payload = [];
        foreach ($external as $item) {
            $snapshot = $snapshots[$item->getSkuId()] ?? null;
            if ($snapshot === null || ($snapshot['fulfillment_provider'] ?? '') !== $item->getFulfillmentProvider()) {
                throw CommerceError::FULFILLMENT_CHANGED->exception();
            }
            $payload[] = $snapshot + ['quantity' => $item->getQuantity()];
        }
        // 插件返回的数据仍须在核心交易边界校验，不能仅信任接口声明。
        /** @var array<string, mixed> $quote */
        $quote = $this->capability->prepare($payload, $order->getAddress()?->toArray() ?? [], $order->getOrderType());
        if (! isset($quote['shipping_fee']) || ! is_int($quote['shipping_fee']) || $quote['shipping_fee'] < 0) {
            throw CommerceError::SHIPPING_QUOTE_INVALID->exception();
        }
        foreach ($external as $item) {
            $result = $quote['items'][$item->getSkuId()] ?? null;
            if (! is_array($result) || ($result['provider'] ?? '') !== $item->getFulfillmentProvider() || empty($result['snapshot'])) {
                throw CommerceError::SNAPSHOT_INCOMPLETE->exception();
            }
            $item->freezeFulfillment($result['provider'], $result['snapshot']);
        }
        $price = $order->getPriceDetail();
        if ($price === null) {
            throw CommerceError::ORDER_AMOUNT_NOT_BUILT->exception();
        }
        $price->setShippingFee($price->getShippingFee() + $quote['shipping_fee']);
        $order->setPriceDetail($price);
    }
}
