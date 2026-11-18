<?php

declare(strict_types=1);

namespace App\Domain\Infrastructure\Plugin\Repository;

use App\Infrastructure\Error\CommerceError;

use App\Infrastructure\Model\Product\Product;
use Hyperf\DbConnection\Db;

/** 所有核心表访问封装在核心仓储，插件仅调用公开契约。 */
final class CommerceProductRepository
{
    public function claim(string $provider, string $key): ?int
    {
        $key = hash('sha256', $key);
        Db::table('commerce_product_imports')->insertOrIgnore([
            'provider' => $provider, 'import_key' => $key,
            'created_at' => date('Y-m-d H:i:s'), 'updated_at' => date('Y-m-d H:i:s'),
        ]);
        $row = Db::table('commerce_product_imports')->where('provider', $provider)->where('import_key', $key)->lockForUpdate()->first();
        if ($row === null) {
            throw CommerceError::IMPORT_CLAIM_FAILED->internal();
        }
        return $row->product_id === null ? null : (int) $row->product_id;
    }

    public function complete(string $provider, string $key, int $productId): void
    {
        Product::query()->whereKey($productId)->update(['fulfillment_provider' => $provider]);
        Db::table('commerce_product_imports')->where('provider', $provider)->where('import_key', hash('sha256', $key))->update([
            'product_id' => $productId, 'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function skuProductByCode(string $code): ?int
    {
        $productId = \App\Infrastructure\Model\Product\ProductSku::query()->where('sku_code', $code)->value('product_id');
        return $productId === null ? null : (int) $productId;
    }

    public function find(int $productId, bool $lock = false): ?array
    {
        $query = Product::query()->with(['skus', 'attributes', 'gallery'])->whereKey($productId);
        if ($lock) {
            $query->lockForUpdate();
        }
        $product = $query->first();
        if ($product === null) {
            return null;
        }
        $data = $product->toArray();
        $data['product_id'] = (int) $product->id;
        $data['skus'] = array_map(static function (array $sku): array {
            $sku['sku_id'] = (int) $sku['id'];
            return $sku;
        }, $data['skus']);
        return $data;
    }
}
