<?php

declare(strict_types=1);

namespace App\Domain\Trade\Order\Contract;

interface StockReservationInterface
{
    public function reserve(string $tradeNo, array $items, string $stockKey): void;
    /** legacy 只用于升级前已扣库存但没有预占标记的订单。 */
    public function release(string $tradeNo, array $items, string $stockKey, bool $legacy = false): void;
}
