<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Trade;

use App\Domain\Trade\Order\Contract\StockReservationInterface;

use App\Infrastructure\Error\InventoryError;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

/** 标记与 SKU 放在同一个 Hash，Lua 在单个 key 内原子执行，兼容 Redis Cluster。 */
final class RedisStockReservation implements StockReservationInterface
{
    public const RESERVE_SCRIPT = <<<'LUA'
local old = redis.call('HGET', KEYS[1], ARGV[1])
if old then
    if old == 'reserved:' .. ARGV[2] then return 1 end
    return -1
end
for i = 3, #ARGV, 2 do
    if tonumber(redis.call('HGET', KEYS[1], ARGV[i]) or '-1') < tonumber(ARGV[i+1]) then return 0 end
end
for i = 3, #ARGV, 2 do
    redis.call('HINCRBY', KEYS[1], ARGV[i], -tonumber(ARGV[i+1]))
    if redis.call('HEXISTS', KEYS[1], '_external_provider:' .. ARGV[i]) == 1 then
        redis.call('HINCRBY', KEYS[1], '_external_held:' .. ARGV[i], tonumber(ARGV[i+1]))
        redis.call('HSET', KEYS[1], '_external_order:' .. string.sub(ARGV[1],14) .. ':' .. ARGV[i], 'reserved:' .. ARGV[i+1])
    end
end
redis.call('HSET', KEYS[1], ARGV[1], 'reserved:' .. ARGV[2])
return 1
LUA;

    public const RELEASE_SCRIPT = <<<'LUA'
local old = redis.call('HGET', KEYS[1], ARGV[1])
local alreadyReleased = old == 'released:' .. ARGV[2]
-- 即便预占响应超时且尚未到达 Redis，也先留下释放标记，阻止延迟预占再次扣库存。
if not old and ARGV[3] ~= '1' then
    redis.call('HSET', KEYS[1], ARGV[1], 'released:' .. ARGV[2])
    return 1
end
if old and old ~= 'reserved:' .. ARGV[2] and not alreadyReleased then return -1 end
local protected = false
for i = 4, #ARGV, 2 do
    if redis.call('HEXISTS', KEYS[1], '_external_provider:' .. ARGV[i]) == 1 then
        local field = '_external_order:' .. string.sub(ARGV[1],14) .. ':' .. ARGV[i]
        local state = redis.call('HGET', KEYS[1], field)
        if state == 'reserved:' .. ARGV[i+1] then
            local held = math.max(0, redis.call('HINCRBY', KEYS[1], '_external_held:' .. ARGV[i], -tonumber(ARGV[i+1])))
            local upstream = tonumber(redis.call('HGET', KEYS[1], '_external_upstream:' .. ARGV[i]) or '0')
            redis.call('HSET', KEYS[1], ARGV[i], math.max(0, upstream-held), field, 'released')
        end
        if state == 'protected:' .. ARGV[i+1] then protected = true end
        -- protected/ack/reconciled 不代表供应商已返库存；客户退款不恢复可售数量。
    elseif not alreadyReleased and redis.call('HEXISTS', KEYS[1], ARGV[i]) == 1 then
        redis.call('HINCRBY', KEYS[1], ARGV[i], tonumber(ARGV[i+1]))
    end
end
redis.call('HSET', KEYS[1], ARGV[1], 'released:' .. ARGV[2])
if protected then return 2 end
return 1
LUA;

    public function __construct(private readonly Redis $redis, private readonly ConfigInterface $config) {}

    public function reserve(string $tradeNo, array $items, string $stockKey): void
    {
        [$digest, $args] = self::normalize($items);
        $result = (int) $this->redis->eval(self::RESERVE_SCRIPT, [
            $this->key($stockKey), '_reservation:' . $tradeNo, $digest, ...$args,
        ], 1);
        if ($result !== 1) {
            throw ($result === 0 ? InventoryError::STOCK_UNAVAILABLE : InventoryError::RESERVATION_MISMATCH)->exception();
        }
    }

    public function release(string $tradeNo, array $items, string $stockKey, bool $legacy = false): void
    {
        [$digest, $args] = self::normalize($items);
        $result = (int) $this->redis->eval(self::RELEASE_SCRIPT, [
            $this->key($stockKey), '_reservation:' . $tradeNo, $digest, $legacy ? '1' : '0', ...$args,
        ], 1);
        if ($result === 2) {
            // 保持 stock.release Outbox 待重试，待采购明确拒绝解除保护或受理确认后完成。
            throw InventoryError::PROTECTED_RELEASE_PENDING->internal();
        }
        if ($result !== 1) {
            throw InventoryError::RELEASE_MISMATCH->exception();
        }
    }

    private function key(string $stockKey): string
    {
        return (string) $this->config->get('cache.default.prefix', '') . $stockKey;
    }

    private static function normalize(array $items): array
    {
        $normalized = [];
        foreach ($items as $item) {
            $id = (int) ($item['sku_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            if ($id <= 0 || $quantity <= 0) {
                throw InventoryError::QUANTITY_INVALID->invalid();
            }
            $normalized[$id] = ($normalized[$id] ?? 0) + $quantity;
        }
        if ($normalized === []) {
            throw InventoryError::ITEMS_REQUIRED->invalid();
        }
        ksort($normalized);
        $args = [];
        foreach ($normalized as $id => $quantity) {
            $args[] = (string) $id;
            $args[] = (string) $quantity;
        }
        return [hash('sha256', json_encode($normalized, JSON_THROW_ON_ERROR)), $args];
    }
}
