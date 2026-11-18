<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Product\Listener;

use App\Domain\Catalog\Product\Event\ProductCreated;
use App\Domain\Catalog\Product\Event\ProductDeleted;
use App\Domain\Catalog\Product\Event\ProductUpdated;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Redis\Redis;

final class ProductSkuStockListener implements ListenerInterface
{
    public const SCRIPT = <<<'LUA'
if redis.call('HEXISTS', KEYS[1], ARGV[1]) == 1 then return 1 end
local changes = cjson.decode(ARGV[2])
local deleted = cjson.decode(ARGV[3])
-- 已有 SKU 缺失库存时拒绝从商品总量重建，避免丢失尚未释放的预占。
for _, item in ipairs(changes) do
    local id = tostring(item.sku_id)
    if item.delta ~= nil and item.delta ~= cjson.null and redis.call('HEXISTS', KEYS[1], id) == 0 and redis.call('HEXISTS', KEYS[1], '_deleted:' .. id) == 0 then return 0 end
end
for _, item in ipairs(changes) do
    local id = tostring(item.sku_id)
    if redis.call('HEXISTS', KEYS[1], '_deleted:' .. id) == 0 then
        if item.delta == nil or item.delta == cjson.null then
            redis.call('HSETNX', KEYS[1], id, item.stock)
        else
            redis.call('HINCRBY', KEYS[1], id, item.delta)
        end
    end
end
for _, id in ipairs(deleted) do
    redis.call('HDEL', KEYS[1], tostring(id))
    redis.call('HSET', KEYS[1], '_deleted:' .. tostring(id), 1)
end
redis.call('HSET', KEYS[1], ARGV[1], 1)
return 1
LUA;

    public function __construct(private readonly Redis $redis, private readonly ConfigInterface $config) {}

    public function listen(): array
    {
        return [ProductCreated::class, ProductUpdated::class, ProductDeleted::class];
    }

    public function process(object $event): void
    {
        if (! $event instanceof ProductCreated && ! $event instanceof ProductUpdated && ! $event instanceof ProductDeleted) {
            return;
        }
        $stocks = match (true) {
            $event instanceof ProductCreated => $event->stockData,
            $event instanceof ProductUpdated => $event->changes->stockAdjustments,
            default => [],
        };
        $deleted = match (true) {
            $event instanceof ProductDeleted => $event->skuIds,
            $event instanceof ProductUpdated => $event->changes->deletedSkuIds,
            default => [],
        };
        if ($stocks === [] && $deleted === []) {
            return;
        }
        $result = $this->redis->eval(self::SCRIPT, [
            (string) $this->config->get('cache.default.prefix', '') . 'product:stock',
            '_adjustment:' . $event->operationId,
            json_encode($stocks, JSON_THROW_ON_ERROR),
            json_encode($deleted, JSON_THROW_ON_ERROR),
        ], 1);
        if ((int) $result !== 1) {
            throw new \RuntimeException('商品库存缺失，保留库存调整事件等待恢复');
        }
    }
}
