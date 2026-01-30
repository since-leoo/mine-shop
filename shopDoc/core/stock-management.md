# 库存管理

本系统使用 **Redis + Lua 脚本** 实现高性能、高并发的库存管理系统，保证库存扣减的原子性和一致性。

## 设计目标

- ✅ **原子性**: 库存检查和扣减必须是原子操作
- ✅ **一致性**: 防止超卖，保证库存数据准确
- ✅ **高性能**: 支持高并发场景（秒杀、团购）
- ✅ **可回滚**: 订单失败时自动恢复库存
- ✅ **分布式锁**: 防止并发冲突

## 架构设计

```
┌─────────────────────────────────────────┐
│         OrderService (订单服务)          │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│      OrderStockService (库存服务)        │
│  - acquireLocks()   获取分布式锁         │
│  - reserve()        扣减库存             │
│  - rollback()       回滚库存             │
│  - releaseLocks()   释放锁               │
└─────────────────────────────────────────┘
                  ↓
┌─────────────────────────────────────────┐
│         Redis + Lua Scripts              │
│  - lock_and_decrement.lua  锁定并扣减    │
│  - rollback.lua            回滚库存       │
└─────────────────────────────────────────┘
```

## 核心流程

### 订单提交流程

```
1. 开始订单提交
   ↓
2. 获取分布式锁 (acquireLocks)
   ↓
3. 执行库存扣减 (reserve - Lua 脚本)
   ├─ 检查库存是否充足
   ├─ 库存充足 → 扣减库存
   └─ 库存不足 → 返回失败
   ↓
4. 保存订单到数据库
   ↓
5. 发布订单创建事件
   ↓
6. 释放分布式锁 (releaseLocks)
   ↓
7. 返回订单信息

异常处理:
- 任何步骤失败 → 执行库存回滚 (rollback)
- 释放所有锁
- 抛出异常
```

## Lua 脚本实现

### 1. 锁定并扣减库存

文件：`app/Infrastructure/Library/Lua/lock_and_decrement.lua`

```lua
-- 参数说明
-- KEYS[1]: 锁的键名
-- KEYS[2]: 库存的键名
-- ARGV[1]: 扣减数量
-- ARGV[2]: 锁的过期时间（秒）

local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])
local lockTtl = tonumber(ARGV[2])

-- 尝试获取分布式锁
if redis.call("SET", lockKey, "1", "NX", "EX", lockTtl) == false then
    return -1  -- 获取锁失败
end

-- 获取当前库存
local currentStock = tonumber(redis.call("GET", stockKey) or 0)

-- 检查库存是否充足
if currentStock < quantity then
    redis.call("DEL", lockKey)  -- 释放锁
    return 0  -- 库存不足
end

-- 扣减库存
redis.call("DECRBY", stockKey, quantity)
return 1  -- 扣减成功
```

**返回值说明**：
- `-1`: 获取锁失败（其他进程正在处理）
- `0`: 库存不足
- `1`: 扣减成功

**特点**：
- 使用 `SET NX EX` 实现分布式锁
- 原子性操作，不会出现竞态条件
- 库存不足时自动释放锁

### 2. 回滚库存

文件：`app/Infrastructure/Library/Lua/rollback.lua`

```lua
-- 参数说明
-- KEYS[1]: 锁的键名
-- KEYS[2]: 库存的键名
-- ARGV[1]: 回滚数量

local lockKey = KEYS[1]
local stockKey = KEYS[2]
local quantity = tonumber(ARGV[1])

-- 恢复库存
if quantity > 0 then
    redis.call("INCRBY", stockKey, quantity)
end

-- 删除锁
redis.call("DEL", lockKey)

return 1  -- 回滚成功
```

**特点**：
- 增加库存数量
- 删除分布式锁
- 保证库存一致性

## 服务实现

### OrderStockService

```php
namespace App\Domain\Order\Service;

use Hyperf\Redis\Redis;

class OrderStockService
{
    private const STOCK_HASH_KEY = 'mall:stock:sku';
    private const LOCK_PREFIX = 'mall:lock:sku:';
    private const LOCK_TTL = 3000; // 锁过期时间（毫秒）
    private const LOCK_RETRY = 5;  // 重试次数

    public function __construct(
        private Redis $redis
    ) {}

    /**
     * 获取分布式锁
     */
    public function acquireLocks(array $items): array
    {
        $locks = [];
        
        foreach ($items as $item) {
            $lockKey = self::LOCK_PREFIX . $item->skuId;
            $locks[$item->skuId] = $lockKey;
        }
        
        return $locks;
    }

    /**
     * 扣减库存
     */
    public function reserve(array $items): void
    {
        $script = file_get_contents(
            BASE_PATH . '/app/Infrastructure/Library/Lua/lock_and_decrement.lua'
        );

        foreach ($items as $item) {
            $lockKey = self::LOCK_PREFIX . $item->skuId;
            $stockKey = self::STOCK_HASH_KEY . ':' . $item->skuId;

            $retry = 0;
            $success = false;

            while ($retry < self::LOCK_RETRY && !$success) {
                $result = $this->redis->eval(
                    $script,
                    [$lockKey, $stockKey, $item->quantity, self::LOCK_TTL],
                    2
                );

                if ($result === 1) {
                    $success = true;
                } elseif ($result === 0) {
                    throw new \RuntimeException(
                        "SKU {$item->skuId} 库存不足"
                    );
                } elseif ($result === -1) {
                    // 获取锁失败，重试
                    $retry++;
                    usleep(100000); // 等待 100ms
                }
            }

            if (!$success) {
                throw new \RuntimeException(
                    "SKU {$item->skuId} 库存锁定失败，请稍后重试"
                );
            }
        }
    }

    /**
     * 回滚库存
     */
    public function rollback(array $items): void
    {
        $script = file_get_contents(
            BASE_PATH . '/app/Infrastructure/Library/Lua/rollback.lua'
        );

        foreach ($items as $item) {
            $lockKey = self::LOCK_PREFIX . $item->skuId;
            $stockKey = self::STOCK_HASH_KEY . ':' . $item->skuId;

            $this->redis->eval(
                $script,
                [$lockKey, $stockKey, $item->quantity],
                2
            );
        }
    }

    /**
     * 释放锁
     */
    public function releaseLocks(array $locks): void
    {
        foreach ($locks as $lockKey) {
            $this->redis->del($lockKey);
        }
    }

    /**
     * 同步库存到 Redis
     */
    public function syncStockToRedis(int $skuId, int $stock): void
    {
        $stockKey = self::STOCK_HASH_KEY . ':' . $skuId;
        $this->redis->set($stockKey, $stock);
    }

    /**
     * 获取 Redis 库存
     */
    public function getStockFromRedis(int $skuId): int
    {
        $stockKey = self::STOCK_HASH_KEY . ':' . $skuId;
        return (int) $this->redis->get($stockKey);
    }
}
```

## 使用示例

### 订单提交时扣减库存

```php
namespace App\Domain\Order\Service;

use App\Domain\Order\Entity\OrderEntity;
use App\Domain\Order\Repository\OrderRepository;

class OrderService
{
    public function __construct(
        private OrderRepository $repository,
        private OrderStockService $stockService
    ) {}

    /**
     * 提交订单
     */
    public function submit(OrderEntity $entity): OrderEntity
    {
        // 1. 验证订单
        $entity->validate();

        // 2. 获取锁
        $locks = $this->stockService->acquireLocks($entity->items);

        try {
            // 3. 扣减库存（Lua 脚本原子操作）
            $this->stockService->reserve($entity->items);

            // 4. 保存订单
            $order = $this->repository->save($entity);

            // 5. 发布事件
            event(new OrderCreatedEvent($order));

            return $order;

        } catch (\Exception $e) {
            // 6. 异常时回滚库存
            $this->stockService->rollback($entity->items);
            throw $e;

        } finally {
            // 7. 释放锁
            $this->stockService->releaseLocks($locks);
        }
    }
}
```

### 订单取消时恢复库存

```php
/**
 * 取消订单
 */
public function cancel(int $orderId, string $reason): bool
{
    $order = $this->repository->find($orderId);

    if (!$order) {
        throw new \RuntimeException('订单不存在');
    }

    // 恢复库存
    $this->stockService->rollback($order->items);

    // 更新订单状态
    return $this->repository->cancel($orderId, $reason);
}
```

## Redis 数据结构

### 库存键

```
键名格式: mall:stock:sku:{sku_id}
类型: String
值: 库存数量（整数）

示例:
mall:stock:sku:1001 = "100"
mall:stock:sku:1002 = "50"
```

### 锁键

```
键名格式: mall:lock:sku:{sku_id}
类型: String
值: "1"
过期时间: 3000 毫秒（3秒）

示例:
mall:lock:sku:1001 = "1" (TTL: 2.5s)
```

## 性能优化

### 1. 使用 Lua 脚本

**优势**：
- 原子性操作，避免竞态条件
- 减少网络往返次数
- 服务器端执行，性能更高

### 2. 分布式锁

**优势**：
- 防止并发冲突
- 自动过期，避免死锁
- 支持重试机制

### 3. 连接池

```php
// config/autoload/redis.php
'pool' => [
    'min_connections' => 10,
    'max_connections' => 100,
    'connect_timeout' => 10.0,
    'wait_timeout' => 3.0,
],
```

### 4. 库存预热

在秒杀活动开始前，将库存数据预热到 Redis：

```php
/**
 * 预热秒杀库存
 */
public function warmupSeckillStock(int $sessionId): void
{
    $products = $this->getSessionProducts($sessionId);
    
    foreach ($products as $product) {
        $this->stockService->syncStockToRedis(
            $product->skuId,
            $product->stock
        );
    }
}
```

## 并发测试

### 测试场景

- 1000 个并发请求
- 每个请求购买 1 件商品
- 商品库存 100 件

### 预期结果

- 100 个请求成功（库存充足）
- 900 个请求失败（库存不足）
- 最终库存为 0
- 无超卖现象

### 测试代码

```php
use Hyperf\Testing\TestCase;

class StockConcurrencyTest extends TestCase
{
    public function testConcurrentReserve()
    {
        $skuId = 1001;
        $initialStock = 100;
        
        // 初始化库存
        $this->stockService->syncStockToRedis($skuId, $initialStock);
        
        // 并发请求
        $successCount = 0;
        $failCount = 0;
        
        $promises = [];
        for ($i = 0; $i < 1000; $i++) {
            $promises[] = async(function () use ($skuId, &$successCount, &$failCount) {
                try {
                    $item = new OrderItemEntity([
                        'skuId' => $skuId,
                        'quantity' => 1,
                    ]);
                    
                    $this->stockService->reserve([$item]);
                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                }
            });
        }
        
        // 等待所有请求完成
        wait($promises);
        
        // 验证结果
        $this->assertEquals(100, $successCount);
        $this->assertEquals(900, $failCount);
        
        $finalStock = $this->stockService->getStockFromRedis($skuId);
        $this->assertEquals(0, $finalStock);
    }
}
```

## 监控和告警

### 1. 库存监控

```php
/**
 * 检查库存预警
 */
public function checkStockWarning(int $skuId): void
{
    $stock = $this->getStockFromRedis($skuId);
    $sku = $this->skuRepository->find($skuId);
    
    if ($stock <= $sku->warningStock) {
        // 发送告警通知
        event(new StockWarningEvent($skuId, $stock));
    }
}
```

### 2. 锁超时监控

```php
/**
 * 监控锁超时
 */
public function monitorLockTimeout(): void
{
    $locks = $this->redis->keys(self::LOCK_PREFIX . '*');
    
    foreach ($locks as $lock) {
        $ttl = $this->redis->ttl($lock);
        
        if ($ttl > self::LOCK_TTL / 1000) {
            // 锁超时告警
            logger()->warning("Lock timeout detected: {$lock}");
        }
    }
}
```

## 常见问题

### 1. 库存不一致怎么办？

定期同步 Redis 库存和数据库库存：

```php
/**
 * 同步库存
 */
public function syncStock(): void
{
    $skus = $this->skuRepository->all();
    
    foreach ($skus as $sku) {
        $redisStock = $this->getStockFromRedis($sku->id);
        
        if ($redisStock !== $sku->stock) {
            logger()->warning("Stock mismatch for SKU {$sku->id}");
            $this->syncStockToRedis($sku->id, $sku->stock);
        }
    }
}
```

### 2. Redis 宕机怎么办？

- 使用 Redis 主从复制
- 使用 Redis Sentinel 实现高可用
- 使用 Redis Cluster 实现分布式

### 3. 锁一直不释放怎么办？

- 设置锁的过期时间（TTL）
- 使用 `finally` 块确保锁被释放
- 监控锁的使用情况

## 下一步

- [订单设计](/core/order-design) - 了解订单系统设计
- [支付系统](/core/payment) - 了解支付系统实现
- [秒杀功能](/features/seckill) - 了解秒杀功能实现
