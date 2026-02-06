# 秒杀实体设计文档

## 实体关系

```
SeckillActivity (秒杀活动)
    ├── ActivityRules (活动规则 - 值对象)
    └── ActivityStatistics (活动统计 - 值对象)
    
    1:N
    
SeckillSession (秒杀场次)
    ├── SessionPeriod (场次时间段 - 值对象)
    ├── SessionRules (场次规则 - 值对象)
    └── ProductStock (场次总库存 - 值对象)
    
    1:N
    
SeckillProduct (秒杀商品)
    ├── ProductPrice (商品价格 - 值对象)
    └── ProductStock (商品库存 - 值对象)
```

## 实体职责

### 1. SeckillActivity (秒杀活动)

**职责**：
- 活动的容器和管理者
- 定义全局规则（适用于所有场次）
- 管理活动状态（pending, active, ended, cancelled）
- 不包含时间信息（时间在场次中）

**核心属性**：
- `id`: 活动ID
- `title`: 活动标题
- `description`: 活动描述
- `status`: 活动状态
- `isEnabled`: 是否启用
- `rules`: 活动规则（ActivityRules值对象）
- `remark`: 备注

**业务规则**：
- 活动可以包含多个场次
- 活动的规则会被场次继承（场次可以覆盖）
- 进行中的活动不能编辑基本信息
- 进行中的活动不能删除
- 已取消或已结束的活动不能启用

**状态转换**：
```
PENDING (待开始)
    ↓ start()
ACTIVE (进行中)
    ↓ end()
ENDED (已结束)

任何状态 → cancel() → CANCELLED (已取消)
```

### 2. SeckillSession (秒杀场次)

**职责**：
- 定义具体的秒杀时间段
- 管理场次级别的库存和限购
- 关联多个秒杀商品
- 计算动态状态（基于时间和库存）

**核心属性**：
- `id`: 场次ID
- `activityId`: 所属活动ID
- `period`: 时间段（SessionPeriod值对象）
- `status`: 场次状态
- `rules`: 场次规则（SessionRules值对象）
- `stock`: 场次总库存（ProductStock值对象）
- `sortOrder`: 排序
- `isEnabled`: 是否启用
- `remark`: 备注

**业务规则**：
- 场次必须属于某个活动
- 场次时间不能超过24小时
- 场次时间不能少于30分钟
- 同一活动下的场次时间不能重叠
- 场次的限购数量不能超过活动的限购数量
- 场次的总库存 = 所有商品库存之和

**动态状态计算**：
```php
public function calculateDynamicStatus(): SeckillStatus
{
    if (!$this->isEnabled) return CANCELLED;
    if ($this->stock->isSoldOut()) return SOLD_OUT;
    if ($this->period->isPending()) return PENDING;
    if ($this->period->isEnded()) return ENDED;
    if ($this->period->isActive()) return ACTIVE;
    return $this->status;
}
```

### 3. SeckillProduct (秒杀商品)

**职责**：
- 关联具体的商品SKU
- 定义秒杀价格
- 管理商品级别的库存和限购
- 计算折扣和优惠

**核心属性**：
- `id`: 秒杀商品ID
- `activityId`: 所属活动ID
- `sessionId`: 所属场次ID
- `productId`: 商品ID
- `productSkuId`: SKU ID
- `price`: 价格（ProductPrice值对象）
- `stock`: 库存（ProductStock值对象）
- `maxQuantityPerUser`: 单品限购
- `sortOrder`: 排序
- `isEnabled`: 是否启用

**业务规则**：
- 商品必须属于某个场次
- 秒杀价必须低于原价
- 秒杀价不能低于0.01元
- 商品的限购数量不能超过场次的限购数量
- 商品库存不能超过场次总库存
- 商品售罄后自动禁用

**价格计算**：
```php
// 折扣率
$discount = $price->getDiscount(); // 例如：30%

// 优惠金额
$savings = $price->getSavings(); // 例如：50元
```

## 值对象设计

### 1. ActivityRules (活动规则)

**用途**：封装活动级别的全局规则

**属性**：
- `maxQuantityPerUser`: 每人最大购买数量（全局）
- `minPurchaseQuantity`: 最小购买数量
- `requireMemberLevel`: 是否要求会员等级
- `requiredMemberLevelId`: 要求的会员等级ID
- `allowRefund`: 是否允许退款
- `refundDeadlineHours`: 退款期限（小时）
- `extraRules`: 额外规则（扩展字段）

**业务方法**：
```php
public function canUserPurchase(int $quantity, ?int $userMemberLevelId = null): bool
```

### 2. SessionPeriod (场次时间段)

**用途**：封装场次的时间范围和时间相关的业务逻辑

**属性**：
- `startTime`: 开始时间
- `endTime`: 结束时间

**业务方法**：
```php
public function isActive(): bool          // 是否进行中
public function isPending(): bool         // 是否待开始
public function isEnded(): bool           // 是否已结束
public function overlaps(SessionPeriod $other): bool  // 是否重叠
public function getDurationInHours(): float           // 时长（小时）
```

### 3. SessionRules (场次规则)

**用途**：封装场次级别的规则（可以覆盖活动规则）

**属性**：
- `maxQuantityPerUser`: 场次每人限购
- `totalQuantity`: 场次总库存
- `allowOverSell`: 是否允许超卖
- `extraRules`: 额外规则

**业务方法**：
```php
public function canPurchase(int $quantity, int $userPurchasedQuantity): bool
```

### 4. ProductPrice (商品价格)

**用途**：封装商品的价格信息和价格相关的计算

**属性**：
- `originalPrice`: 原价
- `seckillPrice`: 秒杀价

**业务方法**：
```php
public function getDiscount(): float      // 折扣率（百分比）
public function getSavings(): float       // 优惠金额
```

### 5. ProductStock (商品库存)

**用途**：封装库存信息和库存相关的业务逻辑

**属性**：
- `quantity`: 总库存
- `soldQuantity`: 已售数量

**业务方法**：
```php
public function getAvailableQuantity(): int           // 可用库存
public function isSoldOut(): bool                     // 是否售罄
public function getStockPercentage(): float           // 库存百分比
public function isLowStock(int $threshold): bool      // 是否低库存
public function canSell(int $quantity): bool          // 是否可售
public function sell(int $quantity): ProductStock     // 扣减库存（返回新对象）
```

### 6. ActivityStatistics (活动统计)

**用途**：封装活动的统计数据

**属性**：
- `totalSessions`: 总场次数
- `activeSessions`: 进行中的场次数
- `totalProducts`: 总商品数
- `totalOrders`: 总订单数
- `totalSales`: 总销售额

**业务方法**：
```php
public function getAverageOrderValue(): float  // 平均订单金额
```

## 业务场景

### 场景1：创建秒杀活动

```php
// 1. 创建活动
$activity = SeckillActivityEntity::create(
    title: '双11秒杀',
    description: '全场5折起',
    rules: new ActivityRules([
        'max_quantity_per_user' => 5,
        'min_purchase_quantity' => 1,
        'allow_refund' => false,
    ])
);

// 2. 创建场次
$session = SeckillSessionEntity::create(
    activityId: $activity->getId(),
    period: new SessionPeriod('2024-11-11 10:00:00', '2024-11-11 12:00:00'),
    rules: new SessionRules([
        'max_quantity_per_user' => 3,  // 覆盖活动规则
        'total_quantity' => 1000,
    ])
);

// 3. 添加商品
$product = SeckillProductEntity::create(
    activityId: $activity->getId(),
    sessionId: $session->getId(),
    productId: 123,
    productSkuId: 456,
    price: new ProductPrice(
        originalPrice: 199.00,
        seckillPrice: 99.00
    ),
    stock: new ProductStock(
        quantity: 100,
        soldQuantity: 0
    ),
    maxQuantityPerUser: 2  // 单品限购
);
```

### 场景2：用户下单

```php
// 1. 检查场次状态
$session = $sessionService->getEntity($sessionId);
if (!$session->canPurchase()) {
    throw new \DomainException('场次不可购买');
}

// 2. 检查商品库存
$product = $productService->getEntity($productId);
if (!$product->canSell($quantity)) {
    throw new \DomainException('库存不足');
}

// 3. 检查用户限购
$userPurchased = $orderRepository->getUserPurchasedQuantity($userId, $sessionId);
if (!$session->getRules()->canPurchase($quantity, $userPurchased)) {
    throw new \DomainException('超过限购数量');
}

// 4. 扣减库存
$product->sell($quantity);
$productRepository->updateFromEntity($product);

// 5. 创建订单
$order = $orderService->create(...);
```

### 场景3：场次状态自动更新

```php
// 定时任务或事件触发
$sessions = $sessionRepository->findPendingSessions();

foreach ($sessions as $session) {
    $entity = SeckillSessionMapper::fromModel($session);
    $newStatus = $entity->calculateDynamicStatus();
    
    if ($newStatus !== $entity->getStatus()) {
        $entity->updateStatus($newStatus);
        $sessionRepository->updateFromEntity($entity);
        
        // 触发状态变更事件
        $eventDispatcher->dispatch(
            new SessionStatusChangedEvent($entity->getId(), $oldStatus, $newStatus)
        );
    }
}
```

## 数据一致性

### 库存一致性

```
活动总库存 = Σ(场次总库存)
场次总库存 = Σ(商品库存)
场次已售 = Σ(商品已售)
```

### 限购一致性

```
商品限购 ≤ 场次限购 ≤ 活动限购
```

### 时间一致性

```
场次时间 ⊆ 活动时间范围（如果活动有时间的话）
同一活动下的场次时间不能重叠
```

## 扩展点

### 1. 规则扩展

通过 `extraRules` 字段可以扩展自定义规则：

```php
$rules = new ActivityRules([
    'max_quantity_per_user' => 5,
    'extra' => [
        'vip_only' => true,
        'new_user_discount' => 0.1,
        'limit_by_region' => ['北京', '上海'],
    ]
]);
```

### 2. 状态扩展

可以通过枚举添加新的状态：

```php
enum SeckillStatus: string
{
    case PENDING = 'pending';
    case ACTIVE = 'active';
    case ENDED = 'ended';
    case CANCELLED = 'cancelled';
    case SOLD_OUT = 'sold_out';
    // 可以添加新状态
    case PAUSED = 'paused';
}
```

### 3. 事件扩展

可以添加更多领域事件：

- `SessionCreatedEvent` - 场次创建
- `ProductAddedEvent` - 商品添加
- `StockWarningEvent` - 库存预警
- `PriceChangedEvent` - 价格变更

## 总结

这个设计遵循DDD原则：

1. **实体**：包含身份标识和生命周期
2. **值对象**：不可变，通过值相等判断
3. **聚合根**：Activity是聚合根，管理Session和Product
4. **业务规则**：封装在实体和值对象中
5. **领域事件**：解耦业务逻辑
6. **仓储模式**：隔离持久化细节

---

**文档版本**：1.0.0  
**创建日期**：2026-02-06  
**维护者**：开发团队
