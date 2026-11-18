<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Repository;

use App\Infrastructure\Model\Order\Order;
use App\Infrastructure\Model\Order\OrderPackage;
use Hyperf\DbConnection\Db;

final class CommerceOrderRepository
{
    public function find(int $id, bool $lock = false): ?array
    {
        $query = Order::query()->with(['items', 'address', 'packages'])->whereKey($id);
        if ($lock) { $query->lockForUpdate(); }
        return $this->snapshot($query->first());
    }

    public function findByNumber(string $number): ?array
    {
        return $this->snapshot(Order::query()->with(['items', 'address', 'packages'])->where('order_no', $number)->first());
    }

    public function paidSince(string $provider, int $afterId, int $limit): array
    {
        return Order::query()->with(['items', 'address', 'packages'])->where('id', '>', $afterId)
            ->whereIn('pay_status', ['paid', 'partial_refunded', 'refunded'])
            ->whereHas('items', static function ($query) use ($provider): void { $query->where('fulfillment_provider', $provider); })
            ->orderBy('id')->limit(min(500, max(1, $limit)))->get()->map(fn ($order) => $this->snapshot($order))->all();
    }

    private function snapshot(?Order $order): ?array
    {
        if ($order === null) { return null; }
        $data = $order->toArray();
        $data['after_sales'] = Db::table('order_trade_after_sale')->where('order_id', $order->id)
            ->select(['id', 'order_item_id', 'type', 'status', 'refund_status', 'quantity', 'apply_amount', 'refund_amount'])
            ->get()->map(static fn ($sale) => (array) $sale)->all();
        return $data;
    }

    public function package(int $orderId, string $provider, string $key): ?array
    {
        return OrderPackage::query()->where('order_id', $orderId)->where('fulfillment_provider', $provider)->where('integration_key', $key)->first()?->toArray();
    }

    public function shippedQuantities(int $orderId): array
    {
        return Db::table('order_package_items')->join('order_packages', 'order_packages.id', '=', 'order_package_items.package_id')
            ->where('order_packages.order_id', $orderId)->groupBy('order_item_id')
            ->selectRaw('order_item_id, SUM(quantity) AS shipped_quantity')->pluck('shipped_quantity', 'order_item_id')->toArray();
    }

    public function savePackage(int $orderId, array $data, array $items, string $status): array
    {
        $package = OrderPackage::query()->create($data + ['order_id' => $orderId]);
        foreach ($items as $id => $quantity) {
            Db::table('order_package_items')->insert([
                'package_id' => $package->id, 'order_item_id' => $id, 'quantity' => $quantity,
                'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
            ]);
        }
        Order::query()->whereKey($orderId)->update([
            'status' => $status, 'shipping_status' => $status,
            'package_count' => OrderPackage::query()->where('order_id', $orderId)->count(),
        ]);
        return $package->toArray();
    }

    public function hasOpenAfterSales(array $itemIds): bool
    {
        return Db::table('order_trade_after_sale')->whereIn('order_item_id', $itemIds)->whereNotIn('status', ['closed'])->exists();
    }
}
