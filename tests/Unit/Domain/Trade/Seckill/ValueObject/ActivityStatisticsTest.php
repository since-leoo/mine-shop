<?php

declare(strict_types=1);

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ActivityStatistics;
use PHPUnit\Framework\TestCase;

class ActivityStatisticsTest extends TestCase
{
    public function testGetters(): void
    {
        $stats = new ActivityStatistics(10, 3, 50, 200, 500000);
        $this->assertSame(10, $stats->getTotalSessions());
        $this->assertSame(3, $stats->getActiveSessions());
        $this->assertSame(50, $stats->getTotalProducts());
        $this->assertSame(200, $stats->getTotalOrders());
        $this->assertSame(500000, $stats->getTotalSales());
    }

    public function testAverageOrderValue(): void
    {
        $stats = new ActivityStatistics(1, 1, 1, 10, 50000);
        $this->assertSame(5000.0, $stats->getAverageOrderValue());
    }

    public function testAverageOrderValueZeroOrders(): void
    {
        $stats = new ActivityStatistics(1, 0, 0, 0, 0);
        $this->assertSame(0.0, $stats->getAverageOrderValue());
    }

    public function testToArray(): void
    {
        $stats = new ActivityStatistics(5, 2, 20, 100, 300000);
        $arr = $stats->toArray();
        $this->assertSame(5, $arr['total_sessions']);
        $this->assertSame(2, $arr['active_sessions']);
        $this->assertSame(20, $arr['total_products']);
        $this->assertSame(100, $arr['total_orders']);
        $this->assertSame(300000, $arr['total_sales']);
        $this->assertSame(3000.0, $arr['average_order_value']);
    }
}
