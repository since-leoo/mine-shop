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

namespace App\Domain\Order\Service;

use App\Domain\Product\Event\ProductStockWarningEvent;
use App\Domain\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Abstract\ICache;
use Hyperf\Stringable\Str;

final class DomainOrderStockService
{
    private const STOCK_HASH_KEY = 'product:stock';

    private const DEDUCT_SCRIPT = <<<'LUA'
        local stockKey = KEYS[1]
        for i = 1, #ARGV, 2 do
            local field = ARGV[i]
            local quantity = tonumber(ARGV[i + 1])
            local current = tonumber(redis.call('HGET', stockKey, field) or '-1')
            if current < quantity then
                return 0
            end
        end
        for i = 1, #ARGV, 2 do
            local field = ARGV[i]
            local quantity = tonumber(ARGV[i + 1])
            redis.call('HINCRBY', stockKey, field, -quantity)
        end
        return 1
        LUA;

    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly int $lockTtl = 3000,
        private readonly int $lockRetry = 5
    ) {}

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<string, string>
     */
    public function acquireLocks(array $items): array
    {
        $locks = [];
        foreach (array_keys($this->normalizeItems($items)) as $skuId) {
            $lockKey = \sprintf('mall:stock:lock:%d', $skuId);
            $token = Str::uuid()->toString();
            $acquired = false;
            for ($i = 0; $i < $this->lockRetry; ++$i) {
                $acquired = (bool) $this->redis()->set($lockKey, $token, ['NX', 'PX' => $this->lockTtl]);
                if ($acquired) {
                    break;
                }
                usleep(50_000);
            }
            if (! $acquired) {
                $this->releaseLocks($locks);
                throw new \RuntimeException('库存繁忙，请稍后重试');
            }
            $locks[$lockKey] = $token;
        }

        return $locks;
    }

    /**
     * @param array<string, string> $locks
     */
    public function releaseLocks(array $locks): void
    {
        if ($locks === []) {
            return;
        }

        $script = <<<'LUA'
            if redis.call('GET', KEYS[1]) == ARGV[1] then
                return redis.call('DEL', KEYS[1])
            end
            return 0
            LUA;

        foreach ($locks as $key => $token) {
            $this->redis()->eval($script, [$key, $token], 1);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function reserve(array $items): void
    {
        $normalized = $this->normalizeItems($items);
        if ($normalized === []) {
            throw new \RuntimeException('没有可扣减的商品');
        }

        $args = [];
        foreach ($normalized as $skuId => $quantity) {
            $args[] = (string) $skuId;
            $args[] = (string) $quantity;
        }

        $prefix = $this->redis()->setPrefix(self::STOCK_HASH_KEY)->getPrefix();
        $payload = array_merge([$prefix], $args);

        $result = $this->redis()->eval(self::DEDUCT_SCRIPT, $payload, 1);
        if ((int) $result !== 1) {
            throw new \RuntimeException('库存不足或商品已下架');
        }

        $this->triggerStockWarnings($normalized);
    }

    /**
     * @param array<int, array<string, mixed>> $items
     */
    public function rollback(array $items): void
    {
        $normalized = $this->normalizeItems($items);
        foreach ($normalized as $skuId => $quantity) {
            $this->redis()->hIncrBy(self::STOCK_HASH_KEY, (string) $skuId, $quantity);
        }
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @return array<int, int>
     */
    private function normalizeItems(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $skuId = (int) ($item['sku_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($skuId <= 0 || $quantity <= 0) {
                continue;
            }
            $result[$skuId] = ($result[$skuId] ?? 0) + $quantity;
        }
        return $result;
    }

    private function redis(): ICache
    {
        return di(ICache::class);
    }

    /**
     * @param array<int, int> $deducted
     */
    private function triggerStockWarnings(array $deducted): void
    {
        $threshold = $this->mallSettingService->product()->stockWarning();
        if ($threshold <= 0) {
            return;
        }

        foreach (array_keys($deducted) as $skuId) {
            $remaining = (int) $this->redis()->hGet(self::STOCK_HASH_KEY, (string) $skuId);
            if ($remaining <= $threshold) {
                event(new ProductStockWarningEvent($skuId, $remaining, $threshold));
            }
        }
    }
}
