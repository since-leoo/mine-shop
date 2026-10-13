<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Contract;

interface ReviewEligibilityInterface
{
    /** @return array{order_id:int, order_item_id:int, product_id:int, sku_id:int} */
    public function eligibleItem(int $memberId, int $orderId, int $orderItemId): array;
}
