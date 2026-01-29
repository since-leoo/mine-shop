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

namespace App\Domain\Product\Listener;

use App\Domain\Product\Event\ProductCreated;
use App\Domain\Product\Event\ProductDeleted;
use App\Domain\Product\Event\ProductUpdated;
use App\Infrastructure\Model\Product\Product;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Redis\Redis;
use Psr\Log\LoggerInterface;

final class ProductSkuStockListener implements ListenerInterface
{
    private const STOCK_HASH_KEY = 'mall:stock:sku';

    public function __construct(
        private readonly Redis $redis,
        private readonly LoggerInterface $logger
    ) {
    }

    public function listen(): array
    {
        return [
            ProductCreated::class,
            ProductUpdated::class,
            ProductDeleted::class,
        ];
    }

    public function process(object $event): void
    {
        try {
            match (true) {
                $event instanceof ProductCreated => $this->syncStocks($event->product),
                $event instanceof ProductUpdated => $this->syncUpdatedStocks($event->product, $event->deletedSkuIds),
                $event instanceof ProductDeleted => $this->clearStocks($event->skuIds),
            };
        } catch (\Throwable $e) {
            $productId = property_exists($event, 'product') && $event->product instanceof Product
                ? $event->product->id
                : null;
            $this->logger->error('Product stock sync failed', [
                'product_id' => $productId,
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function syncStocks(Product $product): void
    {
        $product->loadMissing('skus');
        $payload = [];
        foreach ($product->skus as $sku) {
            $skuId = (int) $sku->id;
            if ($skuId <= 0) {
                continue;
            }
            $payload[(string) $skuId] = (int) $sku->stock;
        }

        if ($payload !== []) {
            $this->redis->hMSet(self::STOCK_HASH_KEY, $payload);
        }
    }

    /**
     * @param array<int, int> $deletedSkuIds
     */
    private function syncUpdatedStocks(Product $product, array $deletedSkuIds): void
    {
        $this->syncStocks($product);

        $this->removeStocks($deletedSkuIds);
    }

    /**
     * @param array<int, int> $skuIds
     */
    private function clearStocks(array $skuIds): void
    {
        $this->removeStocks($skuIds);
    }

    /**
     * @param array<int, int> $skuIds
     */
    private function removeStocks(array $skuIds): void
    {
        $fields = array_values(array_filter(array_map(static function (int $skuId) {
            return $skuId > 0 ? (string) $skuId : null;
        }, $skuIds)));

        if ($fields === []) {
            return;
        }

        $this->redis->hDel(self::STOCK_HASH_KEY, ...$fields);
    }
}
