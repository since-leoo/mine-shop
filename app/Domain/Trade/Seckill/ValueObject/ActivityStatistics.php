<?php

declare(strict_types=1);

namespace App\Domain\Trade\Seckill\ValueObject;

final class ActivityStatistics
{
    public function __construct(
        private readonly int $totalSessions,
        private readonly int $activeSessions,
        private readonly int $totalProducts,
        private readonly int $totalOrders,
        private readonly int $totalSales
    ) {}

    public function getTotalSessions(): int
    {
        return $this->totalSessions;
    }

    public function getActiveSessions(): int
    {
        return $this->activeSessions;
    }

    public function getTotalProducts(): int
    {
        return $this->totalProducts;
    }

    public function getTotalOrders(): int
    {
        return $this->totalOrders;
    }

    public function getTotalSales(): int
    {
        return $this->totalSales;
    }

    public function getAverageOrderValue(): float
    {
        return $this->totalOrders === 0 ? 0.0 : round($this->totalSales / $this->totalOrders, 2);
    }

    public function toArray(): array
    {
        return [
            'total_sessions' => $this->totalSessions, 'active_sessions' => $this->activeSessions,
            'total_products' => $this->totalProducts, 'total_orders' => $this->totalOrders,
            'total_sales' => $this->totalSales, 'average_order_value' => $this->getAverageOrderValue(),
        ];
    }
}
