<?php

declare(strict_types=1);

namespace Plugin\Since\GroupBuy\Domain\Service;

use App\Infrastructure\Abstract\ICache;
use Plugin\Since\GroupBuy\Domain\Repository\GroupBuyRepository;

final class GroupBuyCacheService
{
    private const PREFIX = 'groupbuy';

    public function __construct(
        private readonly ICache $cache,
        private readonly GroupBuyRepository $repository,
    ) {
        $this->cache->setPrefix(self::PREFIX);
    }

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
