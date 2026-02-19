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

namespace App\Domain\Trade\Order\Service;

use App\Domain\Catalog\Product\Event\ProductStockWarningEvent;
use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Infrastructure\Abstract\ICache;
use Hyperf\Stringable\Str;

/**
 * 订单库存领域服务.
 *
 * 负责商品库存的预扣、回滚和分布式锁管理。
 * 使用 Redis Hash 存储库存，Lua 脚本保证原子性扣减。
 *
 * 支持三种库存类型：
 * - 普通商品库存：product:stock
 * - 秒杀库存：seckill:stock:{sessionId}
 * - 团购库存：groupbuy:stock:{groupBuyId}
 */
final class DomainOrderStockService
{
    /**
     * 普通商品库存 Hash Key.
     */
    public const STOCK_HASH_KEY = 'product:stock';

    /**
     * 秒杀库存 Hash Key 前缀，完整 key 为 seckill:stock:{sessionId}.
     */
    public const SECKILL_STOCK_PREFIX = 'seckill:stock';

    /**
     * 团购库存 Hash Key 前缀，完整 key 为 groupbuy:stock:{groupBuyId}.
     */
    public const GROUP_BUY_STOCK_PREFIX = 'groupbuy:stock';

    /**
     * Lua 原子扣库存脚本.
     *
     * 流程：
     * 1. 遍历所有 SKU，检查库存是否充足
     * 2. 如果任一 SKU 库存不足，返回 0
     * 3. 所有检查通过后，批量扣减库存
     * 4. 返回 1 表示成功
     */
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

    /**
     * @param DomainMallSettingService $mallSettingService 商城配置服务
     * @param int $lockTtl 分布式锁过期时间（毫秒）
     * @param int $lockRetry 获取锁的重试次数
     */
    public function __construct(
        private readonly DomainMallSettingService $mallSettingService,
        private readonly int $lockTtl = 3000,
        private readonly int $lockRetry = 5
    ) {}

    /**
     * 获取商品库存分布式锁.
     *
     * 为每个 SKU 获取独立的分布式锁，防止并发扣减导致超卖。
     * 使用 Redis SET NX PX 实现，支持自动过期和重试。
     *
     * @param array<int, array<string, mixed>> $items 商品列表，每项包含 sku_id 和 quantity
     * @param string $stockHashKey 库存 Hash Key
     * @return array<string, string> 锁映射表，key 为锁名，value 为锁令牌
     * @throws \RuntimeException 获取锁失败时抛出
     */
    public function acquireLocks(array $items, string $stockHashKey = self::STOCK_HASH_KEY): array
    {
        $locks = [];
        foreach (array_keys($this->normalizeItems($items)) as $skuId) {
            $lockKey = \sprintf('mall:stock:lock:%s:%d', $stockHashKey, $skuId);
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
     * 释放商品库存分布式锁.
     *
     * 使用 Lua 脚本保证原子性：只有持有正确令牌的客户端才能释放锁。
     *
     * @param array<string, string> $locks 锁映射表，key 为锁名，value 为锁令牌
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
     * 预扣库存（原子操作）.
     *
     * 使用 Lua 脚本在 Redis 中原子性地检查并扣减库存。
     * 如果任一 SKU 库存不足，整个操作失败，不会部分扣减。
     *
     * @param array<int, array<string, mixed>> $items 商品列表，每项包含 sku_id 和 quantity
     * @param string $stockHashKey 库存 Hash Key
     * @throws \RuntimeException 库存不足或商品不存在时抛出
     */
    public function reserve(array $items, string $stockHashKey = self::STOCK_HASH_KEY): void
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

        $prefix = $this->redis()->setPrefix($stockHashKey)->getPrefix();
        $payload = array_merge([$prefix], $args);

        $result = $this->redis()->eval(self::DEDUCT_SCRIPT, $payload, 1);
        if ((int) $result !== 1) {
            throw new \RuntimeException('库存不足或商品已下架');
        }

        $this->triggerStockWarnings($normalized, $stockHashKey);
    }

    /**
     * 回滚库存.
     *
     * 订单取消或支付超时时调用，将预扣的库存返还。
     * 使用 HINCRBY 命令逐个增加库存。
     *
     * @param array<int, array<string, mixed>> $items 商品列表，每项包含 sku_id 和 quantity
     * @param string $stockHashKey 库存 Hash Key
     */
    public function rollback(array $items, string $stockHashKey = self::STOCK_HASH_KEY): void
    {
        $normalized = $this->normalizeItems($items);
        foreach ($normalized as $skuId => $quantity) {
            $this->redis()->hIncrBy($stockHashKey, (string) $skuId, $quantity);
        }
    }

    /**
     * 根据订单类型和活动 ID 解析库存 Hash Key.
     *
     * normal    → product:stock
     * seckill   → seckill:stock:{activityId}（activityId = sessionId）
     * group_buy → groupbuy:stock:{activityId}（activityId = groupBuyId）
     */
    public static function resolveStockKey(string $orderType, int $activityId = 0): string
    {
        return match ($orderType) {
            'seckill' => \sprintf('%s:%d', self::SECKILL_STOCK_PREFIX, $activityId),
            'group_buy' => \sprintf('%s:%d', self::GROUP_BUY_STOCK_PREFIX, $activityId),
            default => self::STOCK_HASH_KEY,
        };
    }

    /**
     * 标准化商品列表.
     *
     * 将商品数组转换为 skuId => quantity 的映射，
     * 同时合并相同 SKU 的数量，过滤无效数据。
     *
     * @param array<int, array<string, mixed>> $items 原始商品列表
     * @return array<int, int> SKU ID 到数量的映射
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

    /**
     * 获取 Redis 缓存实例.
     */
    private function redis(): ICache
    {
        return di(ICache::class);
    }

    /**
     * 触发库存预警事件.
     *
     * 扣减库存后检查剩余库存，如果低于预警阈值则触发事件。
     * 仅对普通商品库存生效，秒杀/团购库存不触发预警。
     *
     * @param array<int, int> $deducted 已扣减的 SKU 和数量
     * @param string $stockHashKey 库存 Hash Key
     */
    private function triggerStockWarnings(array $deducted, string $stockHashKey = self::STOCK_HASH_KEY): void
    {
        // 仅普通商品库存触发预警
        if ($stockHashKey !== self::STOCK_HASH_KEY) {
            return;
        }

        $threshold = $this->mallSettingService->product()->stockWarning();
        if ($threshold <= 0) {
            return;
        }

        foreach (array_keys($deducted) as $skuId) {
            $remaining = (int) $this->redis()->hGet($stockHashKey, (string) $skuId);
            if ($remaining <= $threshold) {
                event(new ProductStockWarningEvent($skuId, $remaining, $threshold));
            }
        }
    }
}
