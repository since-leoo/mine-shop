<?php

declare(strict_types=1);

namespace App\Domain\Trade\AfterSale\ValueObject;

/** 逐项分摊实际商品实付金额，控制多次部分售后的数量与金额上限。 */
final readonly class AfterSaleAllowanceVo
{
    public int $quantity;
    public int $amount;

    public function __construct(array $items, int $itemId, int $paidGoodsAmount, int $usedQuantity, int $usedAmount)
    {
        usort($items, static fn ($a, $b) => (int) $a['id'] <=> (int) $b['id']);
        $total = array_sum(array_column($items, 'total_price'));
        $paidGoodsAmount = min(max(0, $paidGoodsAmount), max(0, $total));
        $allocated = 0;
        $found = null;
        $last = count($items) - 1;
        foreach ($items as $index => $item) {
            $share = $index === $last ? $paidGoodsAmount - $allocated : ($total > 0 ? (int) floor($paidGoodsAmount * (int) $item['total_price'] / $total) : 0);
            $allocated += $share;
            if ((int) $item['id'] === $itemId) { $found = ['quantity' => (int) $item['quantity'], 'amount' => $share]; }
        }
        if ($found === null) { throw new \DomainException('售后商品不属于订单'); }
        $this->quantity = max(0, $found['quantity'] - $usedQuantity);
        $this->amount = max(0, $found['amount'] - $usedAmount);
    }

    public function assertRequest(int $quantity, int $amount): void
    {
        if ($quantity <= 0 || $quantity > $this->quantity || $amount < 0 || $amount > $this->amount) {
            throw new \DomainException('售后数量或金额超过该订单商品剩余可退范围');
        }
    }
}
