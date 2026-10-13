<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\ReviewEligibilityInterface;
use App\Domain\Trade\Order\Enum\OrderStatus;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;

final class ReviewEligibility implements ReviewEligibilityInterface
{
    public function eligibleItem(int $memberId, int $orderId, int $orderItemId): array
    {
        $order = Order::query()->find($orderId);
        if ($order === null) {
            throw new \RuntimeException('订单不存在');
        }
        if ((int) $order->member_id !== $memberId) {
            throw new \DomainException('无权评价该订单');
        }
        if ($order->status !== OrderStatus::COMPLETED->value) {
            throw new \DomainException('只能对已完成的订单进行评价');
        }
        $item = OrderItem::query()->where('order_id', $orderId)->find($orderItemId);
        if ($item === null) {
            throw new \RuntimeException('订单商品不存在');
        }
        return ['order_id' => $orderId, 'order_item_id' => $orderItemId, 'product_id' => (int) $item->product_id, 'sku_id' => (int) $item->sku_id];
    }
}
