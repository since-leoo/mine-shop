<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Repository;

use App\Infrastructure\Error\InventoryError;

use App\Infrastructure\Model\Product\ProductSku;
use App\Infrastructure\Service\Trade\ExternalInventoryProjection;
use Hyperf\DbConnection\Db;

final class CommerceInventoryRepository
{
    public function __construct(private readonly ExternalInventoryProjection $projection) {}

    public function project(string $provider, int $skuId, int $stock, int $version, array $trades): int
    {
        $sku = ProductSku::query()->with('product')->whereKey($skuId)->first();
        if ($sku === null || ($sku->product?->fulfillment_provider ?? 'merchant') !== $provider) {
            throw InventoryError::SKU_PROVIDER_MISMATCH->exception();
        }
        Db::table('commerce_stock_projections')->insertOrIgnore([
            'sku_id' => $skuId, 'provider' => $provider, 'version' => 0, 'upstream_stock' => 0,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row = Db::table('commerce_stock_projections')->where('sku_id', $skuId)->lockForUpdate()->first();
        if ($row === null || $row->provider !== $provider) { throw InventoryError::PROJECTION_OWNER_MISMATCH->exception(); }
        // DB 版本与 Redis 版本都检查；跨存储失败后可按相同版本安全重试。
        if ($version < (int) $row->version) { return (int) $sku->stock; }
        if ($version === (int) $row->version && $stock !== (int) $row->upstream_stock) { throw InventoryError::SNAPSHOT_VERSION_CONFLICT->exception(); }
        $available = $this->projection->project($provider, $skuId, $stock, $version, (int) $row->version === 0, $trades);
        Db::table('commerce_stock_projections')->where('sku_id', $skuId)->update([
            'version' => $version, 'upstream_stock' => $stock, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        ProductSku::query()->whereKey($skuId)->update(['stock' => $available]);
        return $available;
    }

    public function procurementState(string $tradeNo): ?array
    {
        // 与取消、支付、客户售后申请共用订单行锁，避免依赖插件事件的投递进度。
        $order = Db::table('orders')->where('order_no', $tradeNo)->lockForUpdate()->first();
        if ($order === null) { return null; }
        $data = (array) $order;
        $data['items'] = Db::table('order_items')->where('order_id', $order->id)->get()->map(static fn ($item) => (array) $item)->all();
        $data['after_sales'] = Db::table('order_trade_after_sale')->where('order_id', $order->id)->get()->map(static fn ($sale) => (array) $sale)->all();
        return $data;
    }

    public function protect(string $provider, string $tradeNo, array $items): void
    {
        $this->projection->protect($provider, $tradeNo, $items);
    }

    public function clearProtection(string $provider, string $tradeNo, array $items): void
    {
        $this->projection->clearProtection($provider, $tradeNo, $items);
    }

    public function acknowledge(string $provider, string $tradeNo, array $items): void { $this->projection->acknowledge($provider, $tradeNo, $items); }
}
