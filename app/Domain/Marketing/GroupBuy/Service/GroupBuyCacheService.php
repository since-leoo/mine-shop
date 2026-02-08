<?php

declare(strict_types=1);

namespace App\Domain\Marketing\GroupBuy\Service;

use App\Domain\Marketing\GroupBuy\Repository\GroupBuyRepository;
use App\Infrastructure\Abstract\ICache;

/**
 * 团购库存缓存服务.
 *
 * Redis 结构：
 * - groupbuy:stock:{groupBuyId} → Hash field=sku_id value=剩余库存
 *
 * 写入：活动启动时预热（GroupBuyStartJob / 定时任务兜底）
 * 读取：DomainOrderStockService Lua 原子扣减
 */
final class GroupBuyCacheService
{
    private const PREFIX = 'groupbuy';

    public function __construct(
        private readonly ICache $cache,
        private readonly GroupBuyRepository $repository,
    ) {
        $this->cache->setPrefix(self::PREFIX);
    }

    /**
     * 预热团购活动库存到 Redis Hash.
     *
     * Hash key: groupbuy:stock:{groupBuyId}
     * Hash field: sku_id → 剩余库存（total_quantity - sold_quantity）
     */
    public function warmStock(int $groupBuyId): void
    {
        $this->cache->setPrefix(self::PREFIX);

        $model = $this->repository->findById($groupBuyId);
        if (! $model) {
            return;
        }

        $remaining = max(0, (int) $model->total_quantity - (int) $model->sold_quantity);
        $skuId = (int) $model->sku_id;
        if ($skuId <= 0) {
            return;
        }

        $hashKey = $this->stockKey($groupBuyId);

        $this->cache->delete($hashKey);
        $this->cache->hMset($hashKey, [(string) $skuId => (string) $remaining]);
    }

    /**
     * 清除团购活动库存缓存.
     */
    public function evictStock(int $groupBuyId): void
    {
        $this->cache->setPrefix(self::PREFIX);
        $this->cache->delete($this->stockKey($groupBuyId));
    }

    private function stockKey(int $groupBuyId): string
    {
        return \sprintf('stock:%d', $groupBuyId);
    }
}
