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
use App\Infrastructure\Abstract\ICache;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\Log\LoggerInterface;

/**
 * SKU库存监听器：负责同步SKU库存到Redis.
 */
final class ProductSkuStockListener implements ListenerInterface
{
    private const STOCK_HASH_KEY_PREFIX = 'product';

    private const STOCK_HASH_KEY = 'stock';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ICache $redis
    ) {}

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
            $this->redis->setPrefix(self::STOCK_HASH_KEY_PREFIX);

            match (true) {
                $event instanceof ProductCreated => $this->handleCreated($event),
                $event instanceof ProductUpdated => $this->handleUpdated($event),
                $event instanceof ProductDeleted => $this->handleDeleted($event),
            };
        } catch (\Throwable $e) {
            $productId = property_exists($event, 'productId') ? $event->productId : null;
            $this->logger->error('Product stock sync failed', [
                'product_id' => $productId,
                'event' => $event::class,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 处理商品创建事件.
     */
    private function handleCreated(ProductCreated $event): void
    {
        // 直接使用事件中的库存数据，不需要查询数据库
        $this->syncStocks($event->stockData);
    }

    /**
     * 处理商品更新事件.
     */
    private function handleUpdated(ProductUpdated $event): void
    {
        // 更新库存
        $this->syncStocks($event->stockData);

        // 删除已删除的 SKU 库存
        if ($event->changes->hasSkuDeleted()) {
            $this->removeStocks($event->changes->deletedSkuIds);
        }
    }

    /**
     * 处理商品删除事件.
     */
    private function handleDeleted(ProductDeleted $event): void
    {
        $this->removeStocks($event->skuIds);
    }

    /**
     * 同步库存数据到Redis.
     *
     * @param array<int, array{sku_id: int, stock: int}> $stockData
     */
    private function syncStocks(array $stockData): void
    {
        $payload = [];
        foreach ($stockData as $item) {
            $skuId = $item['sku_id'] ?? 0;
            $stock = $item['stock'] ?? 0;

            if ($skuId <= 0) {
                continue;
            }

            $payload[(string) $skuId] = $stock;
        }

        if ($payload !== []) {
            $this->redis->hMSet(self::STOCK_HASH_KEY, $payload);
        }
    }

    /**
     * 删除SKU库存.
     *
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
