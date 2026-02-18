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

namespace HyperfTests\Unit\Domain\Trade\Seckill\ValueObject;

use App\Domain\Trade\Seckill\ValueObject\ActivityStatistics;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 * @coversNothing
 */
final class ActivityStatisticsTest extends TestCase
{
    public function testGetters(): void
    {
        $stats = new ActivityStatistics(10, 3, 50, 200, 500000);
        self::assertSame(10, $stats->getTotalSessions());
        self::assertSame(3, $stats->getActiveSessions());
        self::assertSame(50, $stats->getTotalProducts());
        self::assertSame(200, $stats->getTotalOrders());
        self::assertSame(500000, $stats->getTotalSales());
    }

    public function testAverageOrderValue(): void
    {
        $stats = new ActivityStatistics(1, 1, 1, 10, 50000);
        self::assertSame(5000.0, $stats->getAverageOrderValue());
    }

    public function testAverageOrderValueZeroOrders(): void
    {
        $stats = new ActivityStatistics(1, 0, 0, 0, 0);
        self::assertSame(0.0, $stats->getAverageOrderValue());
    }

    public function testToArray(): void
    {
        $stats = new ActivityStatistics(5, 2, 20, 100, 300000);
        $arr = $stats->toArray();
        self::assertSame(5, $arr['total_sessions']);
        self::assertSame(2, $arr['active_sessions']);
        self::assertSame(20, $arr['total_products']);
        self::assertSame(100, $arr['total_orders']);
        self::assertSame(300000, $arr['total_sales']);
        self::assertSame(3000.0, $arr['average_order_value']);
    }
}
