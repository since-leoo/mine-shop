# 库存管理

为应对秒杀、团购、普通订单等高并发场景，Mine Shop 采用 **Redis + Lua + Hyperf 协程** 的库存管理方案，实现原子扣减、防超卖与失败回滚。本页介绍设计目标、核心流程、脚本实现与最佳实践。

## 设计目标

- **原子性**：库存检查 + 扣减必须在同一原子操作中完成。
- **一致性**：超卖/重复扣减可控，可在回滚或补偿后恢复。
- **高吞吐**：协程 + Redis 内存操作 + Lua，支持瞬时万级 QPS。
- **可回滚**：订单失败、取消、支付超时均能自动归还库存。
- **可观测**：通过日志、指标、报警快速定位异常。

## 架构总览

```
OrderService
   │
   ├─ OrderStockService            // 库存领域服务
   │    ├─ acquireLocks()          // 分布式锁键名计算
   │    ├─ reserve()               // 调用 Redis Lua 扣减
   │    ├─ rollback()              // Lua 回滚
   │    └─ releaseLocks()
   │
   └─ Repository / Event / Log     // 保存订单、触发事件等

Redis
   ├─ mall:stock:sku:{sku_id}      // 库存 Hash / String
   ├─ mall:lock:sku:{sku_id}       // 分布式锁
   └─ Lua 脚本 (lock_and_decrement.lua / rollback.lua)
```

## 锁与键设计

- **库存键**：`mall:stock:sku:{sku_id}`，值为剩余数量。
- **锁键**：`mall:lock:sku:{sku_id}`，值为 1，带过期时间（默认 3 秒）。
- **Lua 参数**：`KEYS[1]` = 锁键，`KEYS[2]` = 库存键，`ARGV[1]` = 扣减数，`ARGV[2]` = 锁 TTL（秒）。

## Lua 扣减脚本（简化）

```lua
-- lock_and_decrement.lua
local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])
local lockTtl = tonumber(ARGV[2])

if redis.call("SET", lockKey, "1", "NX", "EX", lockTtl) == false then
    return -1 -- 获取锁失败
end

local currentStock = tonumber(redis.call("GET", stockKey) or 0)
if currentStock < quantity then
    redis.call("DEL", lockKey)
    return 0 -- 库存不足
end

redis.call("DECRBY", stockKey, quantity)
return 1 -- 扣减成功
```

返回值：`1` 成功、`0` 库存不足、`-1` 获取锁失败（重试）。

## Lua 回滚脚本（简化）

```lua
-- rollback.lua
local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])

if quantity > 0 then
    redis.call("INCRBY", stockKey, quantity)
end
redis.call("DEL", lockKey)
return 1
```

## 扣减流程

1. **收集 SKU**：`OrderSubmitCommand` 中的 SKU/数量被转换为 `StockItem` 列表。
2. **尝试锁定**：`acquireLocks()` 构造锁键，多个 SKU 可按 ID 升序处理避免死锁。
3. **调用 Lua**：`reserve()` 使用 `Redis::eval()` 执行脚本。若返回 `-1` 会在协程内重试，最多 `LOCK_RETRY` 次。
4. **保存订单**：扣减成功后保存订单；若数据库操作失败，立刻调用 `rollback()`。
5. **释放锁**：成功/失败都会释放锁，防止死锁。
6. **异常处理**：任何异常都会抛出业务错误，由应用层捕获并提示「库存不足」等信息。

## 协程与性能

- Hyperf 协程服务器允许在单进程内并发执行多次扣减。
- Redis 连接由连接池管理，Lua 脚本在 Redis 侧一次完成，避免多次往返。
- 对于多 SKU 订单，可在协程中按顺序扣减，失败则立刻回滚前面已扣减的 SKU。

## 超时与补偿

- 锁 TTL 默认为 3 秒，可按需要调整。
- 若订单创建成功但未支付，超时关闭时会通过 `rollback()` 将库存归还。
- 系统提供 `mall:stock:check`（示例）任务，用于定期比对数据库库存与 Redis，防止极端情况下的不一致。

## 监控建议

| 指标 | 说明 |
| ---- | ---- |
| 库存扣减成功率 | `reserve()` 返回 1 的百分比 |
| 获取锁失败次数 | `-1` 统计，若持续升高需分析热点 SKU |
| 回滚次数 | 高频回滚可能意味着下单失败过多 |
| Redis 延迟 | 监控 `eval` 命令耗时 |

可通过 Prometheus / Grafana 或自建日志（`stock.log`）观察。

## 最佳实践

1. **SKU 预热**：上线前将初始库存写入 Redis，并使用 `KEYS` 或脚本校验。
2. **多端一致**：前端实时更新库存展示，但实际库存以扣减脚本为准，前端结果仅作参考。
3. **防重入**：订单创建使用幂等键，防止重复创建导致重复扣减。
4. **降级策略**：在 Redis 异常时可切换到数据库乐观锁模式（性能下降但可维持下单）。
5. **串联营销**：秒杀、团购等模块直接复用 `OrderStockService`，不需要单独实现库存逻辑。

凭借上述方案，Mine Shop 在峰值秒杀场景下也能保证库存准确、并发安全与系统可观测性。*** End Patch
