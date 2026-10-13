<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use App\Domain\Infrastructure\Plugin\Contract\CommerceOverviewInterface;
use App\Infrastructure\Model\Member\Member;
use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Product\Product;
use Carbon\Carbon;

final class CommerceOverview implements CommerceOverviewInterface
{
    public function overview(): array
    {
        $today = Carbon::today();
        $todayStart = $today->copy()->startOfDay();
        $todayEnd = $today->copy()->endOfDay();

        // 今日实时数据
        $todayOrders = Order::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $todaySales = (int) Order::query()
            ->where('pay_status', 'paid')
            ->whereBetween('pay_time', [$todayStart, $todayEnd])
            ->sum('pay_amount');
        $todayNewMembers = Member::query()->whereBetween('created_at', [$todayStart, $todayEnd])->count();
        $todayActiveMembers = Member::query()->whereBetween('last_login_at', [$todayStart, $todayEnd])->count();

        // 待处理事项
        $pendingOrders = Order::query()->where('status', 'pending')->count();
        $paidAwaitShip = Order::query()->where('status', 'paid')->count();
        $lowStockProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('skus', static function ($q) {
                $q->where('stock', '<=', 10)->where('stock', '>', 0);
            })
            ->count();
        $outOfStockProducts = Product::query()
            ->where('status', 'active')
            ->whereHas('skus', static function ($q) {
                $q->where('stock', '<=', 0);
            })
            ->count();

        // 总览数据
        $totalMembers = Member::query()->count();
        $totalProducts = Product::query()->where('status', 'active')->count();
        $totalOrders = Order::query()->count();
        $totalSales = (int) Order::query()->where('pay_status', 'paid')->sum('pay_amount');

        return ['today' => ['orders' => $todayOrders, 'sales' => $todaySales, 'new_members' => $todayNewMembers, 'active_members' => $todayActiveMembers], 'pending' => ['pending_payment' => $pendingOrders, 'pending_shipment' => $paidAwaitShip, 'low_stock' => $lowStockProducts, 'out_of_stock' => $outOfStockProducts], 'overview' => ['total_members' => $totalMembers, 'total_products' => $totalProducts, 'total_orders' => $totalOrders, 'total_sales' => $totalSales]];
    }
}
