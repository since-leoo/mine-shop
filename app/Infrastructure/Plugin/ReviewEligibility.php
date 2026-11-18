<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\ReviewEligibilityInterface;

use App\Domain\Trade\Order\Enum\OrderStatus;

use App\Infrastructure\Error\CommerceError;
use App\Infrastructure\Error\ReviewAccessError;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderItem;

final class ReviewEligibility implements ReviewEligibilityInterface
{
    public function eligibleItem(int $memberId, int $orderId, int $orderItemId): array
    {
        $order = Order::query()->find($orderId);
        if ($order === null) {
            throw CommerceError::ORDER_NOT_FOUND->internal();
        }
        if ((int) $order->member_id !== $memberId) {
            throw ReviewAccessError::ORDER_FORBIDDEN->exception();
        }
        if ($order->status !== OrderStatus::COMPLETED->value) {
            throw ReviewAccessError::ORDER_NOT_COMPLETED->exception();
        }
        $item = OrderItem::query()->where('order_id', $orderId)->find($orderItemId);
        if ($item === null) {
            throw ReviewAccessError::ITEM_NOT_FOUND->internal();
        }
        return ['order_id' => $orderId, 'order_item_id' => $orderItemId, 'product_id' => (int) $item->product_id, 'sku_id' => (int) $item->sku_id];
    }
}
