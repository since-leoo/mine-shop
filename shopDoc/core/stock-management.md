# 库存管理

为应对秒杀、团购、普通订单等高并发场景，Mine Shop 采用 **Redis Hash + Lua + Hyperf 协程** 的库存管理方案，实现原子扣减、防超卖与失败回滚。

## 设计目标

- **原子性**：库存检查 + 扣减在同一 Lua 脚本中完成
- **一致性**：超卖可控，失败自动回滚
- **高吞吐**：协程 + Redis 内存操作，支持瞬时万级 QPS
- **可回滚**：订单失败、取消、支付超时均能归还库存
- **可观测**：库存预警事件 + 日志

## 架构总览

```
DomainApiOrderCommandService
   │
   ├─ DomainOrderStockService          // 库存领域服务
   │    ├─ acquireLocks(items)         // 分布式锁（按 SKU ID 升序）
   │    ├─ reserve(items)              // Lua 原子扣减
   │    ├─ rollback(items)             // HINCRBY 回滚
   │    └─ releaseLocks(locks)         // 释放锁
   │
   └─ OrderRepository / Event          // 保存订单、触发事件

Redis
   ├─ product:stock (Hash)             // field=skuId, value=库存数
   └─ mall:stock:lock:{skuId}          // 分布式锁（NX + PX TTL）
```

## 键设计

| 键 | 类型 | 说明 |
| -- | ---- | ---- |
| `product:stock` | Hash | 所有 SKU 库存，field 为 `skuId`，value 为剩余数量 |
| `mall:stock:lock:{skuId}` | String | 分布式锁，值为 UUID token，TTL 默认 3000ms |

使用 Hash 而非独立 String 键，减少 Redis 键数量，便于批量操作。

## Lua 扣减脚本

内嵌在 `DomainOrderStockService` 中，支持多 SKU 批量扣减：

```lua
-- 第一轮：检查所有 SKU 库存是否充足
local stockKey = KEYS[1]
for i = 1, #ARGV, 2 do
    local field = ARGV[i]
    local quantity = tonumber(ARGV[i + 1])
    local current = tonumber(redis.call('HGET', stockKey, field) or '-1')
    if current < quantity then
        return 0  -- 库存不足
    end
end
-- 第二轮：全部充足后批量扣减
for i = 1, #ARGV, 2 do
    local field = ARGV[i]
    local quantity = tonumber(ARGV[i + 1])
    redis.call('HINCRBY', stockKey, field, -quantity)
end
return 1  -- 扣减成功
```

先检查后扣减，保证要么全部成功要么全部不扣。返回 `1` 成功，`0` 库存不足。

## 回滚

回滚直接使用 `HINCRBY` 逐个归还：

```php
public function rollback(array $items): void
{
    foreach ($normalized as $skuId => $quantity) {
        $this->redis()->hIncrBy('product:stock', (string) $skuId, $quantity);
    }
}
```

## 分布式锁

`acquireLocks()` 按 SKU ID 升序获取锁，避免死锁：

- 锁键：`mall:stock:lock:{skuId}`
- 值：UUID token（释放时校验，防止误删）
- TTL：默认 3000ms
- 重试：最多 5 次，每次间隔 50ms
- 获取失败：释放已获取的锁，抛出「库存繁忙」异常

释放锁使用 Lua 脚本保证原子性（GET + DEL）：

```lua
if redis.call('GET', KEYS[1]) == ARGV[1] then
    return redis.call('DEL', KEYS[1])
end
return 0
```

## 完整扣减流程

在 `DomainApiOrderCommandService::submit()` 中：

```php
$locks = $this->stockService->acquireLocks($items);
try {
    $this->stockService->reserve($items);
    try {
        $entity = $this->repository->save($entity);
        $this->markCouponsUsed($entity);
        $strategy->postCreate($entity);
    } catch (\Throwable $e) {
        $this->stockService->rollback($items);
        throw $e;
    }
} finally {
    $this->stockService->releaseLocks($locks);
}
```

关键保障：
- `reserve()` 成功后若 `save()` 失败，立即 `rollback()`
- `finally` 块确保锁一定释放
- 异常向上抛出，由应用层返回错误信息

## 库存预警

扣减成功后，`triggerStockWarnings()` 检查剩余库存：

- 阈值从 `DomainMallSettingService::product()->stockWarning()` 获取
- 低于阈值时触发 `ProductStockWarningEvent`
- 可对接通知、后台提醒等

## 协程与性能

- Hyperf 协程服务器允许单进程内并发执行多次扣减
- Redis 连接由连接池管理，Lua 脚本在 Redis 侧一次完成
- Hash 结构减少键数量，批量扣减减少网络往返

## 最佳实践

1. **SKU 预热**：上线前将初始库存写入 `product:stock` Hash
2. **多端一致**：前端库存展示仅作参考，实际以扣减脚本为准
3. **防重入**：订单创建使用幂等键，防止重复扣减
4. **降级策略**：Redis 异常时可切换到数据库乐观锁模式
5. **串联营销**：秒杀、团购复用 `DomainOrderStockService`，不需要单独实现库存逻辑

凭借上述方案，Mine Shop 在峰值秒杀场景下也能保证库存准确、并发安全与系统可观测性。
