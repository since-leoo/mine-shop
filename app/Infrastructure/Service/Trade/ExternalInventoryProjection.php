<?php

declare(strict_types=1);

namespace App\Infrastructure\Service\Trade;

use App\Infrastructure\Error\InventoryError;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Redis\Redis;

/** 所有计数与预占共用同一个 Hash；不需要跨 key Lua，兼容 Redis Cluster。 */
final class ExternalInventoryProjection
{
    public const PROJECT_SCRIPT = <<<'LUA'
local sku, provider, stock, version = ARGV[1], ARGV[2], tonumber(ARGV[3]), tonumber(ARGV[4])
local marker = redis.call('HGET', KEYS[1], '_external_provider:' .. sku)
if marker and marker ~= provider then return -1 end
if not marker and ARGV[5] ~= '1' then return -2 end
local previous = tonumber(redis.call('HGET', KEYS[1], '_external_version:' .. sku) or '0')
if version == previous and stock ~= tonumber(redis.call('HGET', KEYS[1], '_external_upstream:' .. sku) or '-1') then return -4 end
if version <= previous then return tonumber(redis.call('HGET', KEYS[1], sku) or '0') end
-- 先验证完整对账批次，再改变任何数量。
for i = 6, #ARGV do
    local field = '_external_order:' .. ARGV[i] .. ':' .. sku
    local state = redis.call('HGET', KEYS[1], field)
    if not state or (string.sub(state,1,4) ~= 'ack:' and string.sub(state,1,11) ~= 'reconciled:') then return -3 end
end
for i = 6, #ARGV do
    local field = '_external_order:' .. ARGV[i] .. ':' .. sku
    local state = redis.call('HGET', KEYS[1], field)
    if string.sub(state,1,11) ~= 'reconciled:' then
        redis.call('HINCRBY', KEYS[1], '_external_held:' .. sku, -tonumber(string.sub(state,5)))
        redis.call('HSET', KEYS[1], field, 'reconciled:' .. string.sub(state,5))
    end
end
local held = math.max(0, tonumber(redis.call('HGET', KEYS[1], '_external_held:' .. sku) or '0'))
local available = math.max(0, stock - held)
redis.call('HSET', KEYS[1], '_external_provider:' .. sku, provider, '_external_version:' .. sku, version, '_external_upstream:' .. sku, stock, sku, available)
return available
LUA;

    public const PROTECT_SCRIPT = <<<'LUA'
-- 全单释放先于发请求时拒绝，即便该SKU先前已有保护，也不能再次发送。
local reservation = redis.call('HGET', KEYS[1], '_reservation:' .. ARGV[2])
if not reservation or string.sub(reservation, 1, 9) ~= 'reserved:' then return -3 end
for i = 3, #ARGV, 2 do
    local sku, quantity = ARGV[i], ARGV[i+1]
    if redis.call('HGET', KEYS[1], '_external_provider:' .. sku) ~= ARGV[1] then return -1 end
    local value = redis.call('HGET', KEYS[1], '_external_order:' .. ARGV[2] .. ':' .. sku)
    if value ~= 'reserved:' .. quantity and value ~= 'protected:' .. quantity then return -2 end
end
for i = 3, #ARGV, 2 do
    redis.call('HSET', KEYS[1], '_external_order:' .. ARGV[2] .. ':' .. ARGV[i], 'protected:' .. ARGV[i+1])
end
return 1
LUA;

    public const CLEAR_PROTECTION_SCRIPT = <<<'LUA'
for i = 3, #ARGV, 2 do
    local sku, quantity = ARGV[i], ARGV[i+1]
    if redis.call('HGET', KEYS[1], '_external_provider:' .. sku) ~= ARGV[1] then return -1 end
    local value = redis.call('HGET', KEYS[1], '_external_order:' .. ARGV[2] .. ':' .. sku)
    if value ~= 'protected:' .. quantity and value ~= 'reserved:' .. quantity and value ~= 'released' then return -2 end
end
-- 只解除保护。若客户退款已发起，原 stock.release Outbox 的下一次重试负责回补。
for i = 3, #ARGV, 2 do
    local field = '_external_order:' .. ARGV[2] .. ':' .. ARGV[i]
    if redis.call('HGET', KEYS[1], field) == 'protected:' .. ARGV[i+1] then
        redis.call('HSET', KEYS[1], field, 'reserved:' .. ARGV[i+1])
    end
end
return 1
LUA;

    public const ACK_SCRIPT = <<<'LUA'
for i = 3, #ARGV, 2 do
    local sku, quantity = ARGV[i], ARGV[i+1]
    if redis.call('HGET', KEYS[1], '_external_provider:' .. sku) ~= ARGV[1] then return -1 end
    local value = redis.call('HGET', KEYS[1], '_external_order:' .. ARGV[2] .. ':' .. sku)
    if value ~= 'reserved:' .. quantity and value ~= 'protected:' .. quantity and value ~= 'ack:' .. quantity and value ~= 'reconciled:' .. quantity then return -2 end
end
for i = 3, #ARGV, 2 do
    local field = '_external_order:' .. ARGV[2] .. ':' .. ARGV[i]
    if redis.call('HGET', KEYS[1], field) ~= 'reconciled:' .. ARGV[i+1] then redis.call('HSET', KEYS[1], field, 'ack:' .. ARGV[i+1]) end
end
return 1
LUA;

    public function __construct(private readonly Redis $redis, private readonly ConfigInterface $config) {}

    public function project(string $provider, int $skuId, int $stock, int $version, bool $initialize, array $trades): int
    {
        $result = (int) $this->redis->eval(self::PROJECT_SCRIPT, [
            $this->key(), (string) $skuId, $provider, (string) $stock, (string) $version, $initialize ? '1' : '0', ...$trades,
        ], 1);
        if ($result < 0) {
            throw (match ($result) {
                -2 => InventoryError::PROJECTION_MISSING,
                -3 => InventoryError::RECONCILIATION_UNCONFIRMED,
                -4 => InventoryError::SNAPSHOT_VERSION_CONFLICT,
                default => InventoryError::PROJECTION_PROVIDER_MISMATCH,
            })->exception();
        }
        return $result;
    }

    public function protect(string $provider, string $tradeNo, array $items): void
    {
        $this->transition(self::PROTECT_SCRIPT, $provider, $tradeNo, $items, InventoryError::PROTECT_FAILED);
    }

    public function clearProtection(string $provider, string $tradeNo, array $items): void
    {
        $this->transition(self::CLEAR_PROTECTION_SCRIPT, $provider, $tradeNo, $items, InventoryError::CLEAR_PROTECTION_FAILED);
    }

    public function acknowledge(string $provider, string $tradeNo, array $items): void
    {
        $this->transition(self::ACK_SCRIPT, $provider, $tradeNo, $items, InventoryError::ACKNOWLEDGE_MISMATCH);
    }

    private function transition(string $script, string $provider, string $tradeNo, array $items, InventoryError $error): void
    {
        $normalized = [];
        foreach ($items as $item) {
            $id = (int) ($item['sku_id'] ?? 0);
            $quantity = $item['quantity'] ?? 0;
            if ($id <= 0 || ! is_int($quantity) || $quantity <= 0) { throw InventoryError::PROCUREMENT_OPERATION_INVALID->invalid(); }
            $normalized[$id] = ($normalized[$id] ?? 0) + $quantity;
        }
        if ($normalized === [] || $tradeNo === '') { throw InventoryError::PROCUREMENT_ITEMS_REQUIRED->invalid(); }
        $args = [];
        foreach ($normalized as $id => $quantity) { $args[] = (string) $id; $args[] = (string) $quantity; }
        $result = (int) $this->redis->eval($script, [$this->key(), $provider, $tradeNo, ...$args], 1);
        if ($result !== 1) { throw $error->exception(); }
    }

    private function key(): string { return (string) $this->config->get('cache.default.prefix', '') . 'product:stock'; }
}
