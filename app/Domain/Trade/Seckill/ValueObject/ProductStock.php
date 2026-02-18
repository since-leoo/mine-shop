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

namespace App\Domain\Trade\Seckill\ValueObject;

final class ProductStock
{
    private readonly int $quantity;

    private readonly int $soldQuantity;

    public function __construct(int $quantity, int $soldQuantity = 0)
    {
        $this->quantity = $quantity;
        $this->soldQuantity = $soldQuantity;
        $this->validate();
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function getSoldQuantity(): int
    {
        return $this->soldQuantity;
    }

    public function getAvailableQuantity(): int
    {
        return $this->quantity - $this->soldQuantity;
    }

    public function isSoldOut(): bool
    {
        return $this->soldQuantity >= $this->quantity;
    }

    public function getStockPercentage(): float
    {
        if ($this->quantity === 0) {
            return 0;
        }
        return round(($this->quantity - $this->soldQuantity) / $this->quantity * 100, 2);
    }

    public function isLowStock(int $threshold = 20): bool
    {
        return $this->getStockPercentage() <= $threshold;
    }

    public function canSell(int $requestQuantity): bool
    {
        return $this->getAvailableQuantity() >= $requestQuantity;
    }

    public function sell(int $quantity): self
    {
        $newSoldQuantity = $this->soldQuantity + $quantity;
        if ($newSoldQuantity > $this->quantity) {
            throw new \DomainException('库存不足');
        }
        return new self($this->quantity, $newSoldQuantity);
    }

    public function toArray(): array
    {
        return [
            'quantity' => $this->quantity,
            'sold_quantity' => $this->soldQuantity,
            'available_quantity' => $this->getAvailableQuantity(),
        ];
    }

    private function validate(): void
    {
        if ($this->soldQuantity > $this->quantity) {
            throw new \InvalidArgumentException('已售数量不能大于总库存');
        }
    }
}
