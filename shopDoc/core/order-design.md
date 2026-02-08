# 订单系统设计

订单是 Mine Shop 的核心聚合根，承担商品、库存、营销、会员资产与履约模块之间的纽带。

## 领域模型

```
OrderEntity
├── OrderItemEntity[]      // SKU 快照、单价、数量、优惠信息
├── OrderAddressValue      // 收货地址快照（含 region_path）
├── OrderPriceValue        // 金额拆解（单位：分）
├── OrderShipEntity        // 包裹/物流信息
└── OrderLogValue[]        // 状态日志、操作者、备注
```

**关键字段**：

| 字段 | 类型 | 说明 |
| ---- | ---- | ---- |
| `order_no` | string | 业务幂等键，提交时生成 |
| `member_id` | int | 会员 ID |
| `order_type` | string | `normal` / `seckill` / `group_buy` |
| `status` | string | 订单状态 |
| `pay_status` | string | `pending` / `paid` / `failed` / `refunded` |
| `shipping_status` | string | `pending` / `partial_shipped` / `shipped` / `delivered` |
| `goods_amount` | int | 商品总额（分） |
| `shipping_fee` | int | 运费（分） |
| `discount_amount` | int | 优惠金额（分） |
| `total_amount` | int | 应付 = 商品 - 优惠（分） |
| `pay_amount` | int | 实付 = 应付 + 运费（分） |
| `coupon_amount` | int | 优惠券抵扣金额（分） |

## 金额值对象

所有金额使用 **int（分）**，避免浮点精度问题：

```php
final class OrderPriceValue
{
    private int $goodsAmount = 0;
    private int $discountAmount = 0;
    private int $shippingFee = 0;
    private int $totalAmount = 0;   // 自动计算：goodsAmount - discountAmount
    private int $payAmount = 0;     // 自动计算：totalAmount + shippingFee
}
```

设置 `goodsAmount`、`discountAmount`、`shippingFee` 时自动触发 `recalculate()`。

## 状态机

```
PENDING (待支付)
  ├─ 支付成功 → PAID
  │     └─ 发货 → SHIPPED → 确认收货 → COMPLETED
  └─ 超时/取消 → CANCELLED

PAID
  └─ 退款 → REFUNDED
```

状态变更由 `DomainOrderService` 集中处理。

## 订单类型策略

不同订单类型（普通、秒杀、拼团）差异在验证逻辑、商品构建、运费、优惠券、后置处理。使用策略模式解耦：

```php
interface OrderTypeStrategyInterface
{
    public function type(): string;
    public function validate(OrderEntity $orderEntity): void;
    public function buildDraft(OrderEntity $orderEntity): OrderEntity;
    public function applyFreight(OrderEntity $orderEntity): void;
    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void;
    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void;
    public function postCreate(OrderEntity $orderEntity): void;
}
```

| 方法 | 职责 | 调用时机 |
|------|------|----------|
| `validate()` | 校验订单合法性（会员、商品） | 同步阶段 |
| `buildDraft()` | 查询商品快照、计算商品金额 | 同步阶段 |
| `applyFreight()` | 计算并设置运费 | 同步阶段 |
| `applyCoupon()` | 优惠券验证与折扣计算（一次一张） | 同步阶段 |
| `rehydrate()` | 从快照恢复活动实体 | 异步 Job |
| `postCreate()` | 后置处理（含优惠券核销） | 异步 Job |

策略注册方式：
- `NormalOrderStrategy` — 主应用 `dependencies.php` 注册
- `SeckillOrderStrategy` — 秒杀插件 `boot()` 动态注册
- `GroupBuyOrderStrategy` — 拼团插件 `boot()` 动态注册

## 下单流程（异步提交架构）

由 `DomainApiOrderCommandService::submit()` 编排，分为同步和异步两个阶段：

### 同步阶段

```
1. buildEntityFromInput()           ← 从 OrderSubmitInput 构建 Entity + 解析地址
2. strategy.validate()              ← 策略验证（会员、商品项）
3. strategy.buildDraft()            ← 构建订单草稿（查快照、校验上下架、计算商品金额）
4. strategy.applyFreight()          ← 运费计算（各策略自行处理）
5. strategy.applyCoupon(couponId)   ← 优惠券验证与折扣计算（一次一张）
6. entity.verifyPrice()             ← 校验前端传入金额与计算金额一致
7. stockService.reserve()           ← Redis Lua 原子扣减库存（无需分布式锁）
8. pendingCache.markProcessing()    ← Redis 标记下单状态为 processing
9. driverFactory.push(OrderCreateJob) ← 投递异步 Job
```

同步阶段返回带 `tradeNo` 的 Entity（status=processing），前端通过 `tradeNo` 轮询下单结果。

### 异步阶段（OrderCreateJob）

```
1. rebuildEntity()                  ← 从快照重建 OrderEntity
2. applySubmissionPolicy()          ← 设置订单过期时间（系统配置）
3. strategy.rehydrate()             ← 策略恢复活动实体（秒杀场次/拼团活动等）
4. DB::transaction()
   ├── repository.save()            ← 持久化订单（生成 order_no）
   └── strategy.postCreate()        ← 后置处理（优惠券核销等）
5. pendingCache.markCreated()       ← 标记下单成功
6. event(OrderCreatedEvent)         ← 触发订单创建事件
```

### 失败处理

`OrderCreateJob` 最多重试 3 次。最终失败时：
- `stockService.rollback()` — 回滚 Redis 库存
- `pendingCache.markFailed()` — 标记下单失败，前端轮询可获取错误信息

### 运费计算

运费计算已下沉到各策略的 `applyFreight()` 方法。`NormalOrderStrategy` 通过 `FreightServiceInterface` 调用运费插件：

- `free`：包邮
- `flat`：固定运费
- `template`：运费模板（按重量/件数/体积阶梯计费）
- `default`：使用系统默认运费配置

支持偏远地区加价。运费写入 `OrderPriceValue.shippingFee`。

秒杀/拼团订单可在各自策略中实现不同的运费逻辑（如秒杀包邮）。

### 优惠券应用

优惠券应用同样下沉到各策略的 `applyCoupon()` 方法。一次只能使用一张优惠券。`NormalOrderStrategy` 通过 `CouponServiceInterface` 调用优惠券插件：

1. 查找会员可用优惠券（`findUsableCoupon`）
2. 校验状态、满减门槛
3. 计算折扣金额（支持 `fixed` 面值直减和 `percent` 折扣）
4. 写入 Entity 的 `couponAmount` 和 `discountAmount`

优惠券核销在异步 Job 的 `postCreate()` 中执行（`settleCoupon`）。

### 预览流程

`preview()` 与 `submit()` 共享 `buildOrder()` 方法（步骤 1-5），不扣库存、不投递 Job，返回金额明细供前端展示确认页。

## 流程图

```
[OrderSubmitInput]
      ↓ buildEntityFromInput + 地址解析
[OrderEntity Draft]
      ↓ validate → buildDraft → applyFreight → applyCoupon
[价格校验] ──不一致→ 抛错
      ↓
[Lua 原子扣库存] ──失败→ 抛错
      ↓ 成功
[Redis 标记 processing] → [投递 OrderCreateJob]
      ↓                         ↓
[返回 tradeNo]          [异步：rehydrate → save → postCreate]
      ↓                         ↓ 失败
[前端轮询]               [回滚库存 + 标记失败]
```

## 接口解耦

订单核心不直接依赖优惠券和运费的具体实现，而是通过接口契约解耦：

| 接口 | 所在位置 | 插件实现 |
|------|----------|----------|
| `CouponServiceInterface` | `app/Domain/Trade/Order/Contract/` | coupon 插件 |
| `FreightServiceInterface` | `app/Domain/Trade/Order/Contract/` | shipping 插件 |

`NormalOrderStrategy` 的构造函数只注入这两个接口 + `ProductSnapshotInterface`：

```php
public function __construct(
    private readonly ProductSnapshotInterface $snapshotService,
    private readonly CouponServiceInterface $couponServiceInterface,
    private readonly FreightServiceInterface $freightServiceInterface,
) {}
```

插件通过 ConfigProvider 的 `dependencies` 注册绑定，Hyperf DI 容器自动装配。详见 [插件系统](/architecture/plugin-system)。

## 关键扩展点

| 扩展点 | 说明 |
| ------ | ---- |
| `OrderTypeStrategyInterface` | 新增订单类型（预售、订阅等），在插件 `boot()` 中注册 |
| `FreightServiceInterface` | 运费规则扩展，由 shipping 插件实现 |
| `CouponServiceInterface` | 优惠券逻辑扩展，由 coupon 插件实现 |
| `DomainOrderStockService` | 可替换为消息队列或分库方案 |
| 领域事件 | `OrderCreated`、`OrderPaid`、`OrderCancelled` 等监听器可扩展 |
| `OrderRepository` | 若需 ES/OLAP 查询，可新增读模型实现 |

## 与其他模块的交互

- **库存**：订单提交 → Lua 原子扣减；Job 失败 → 自动回滚
- **营销**：优惠券核销在策略 `postCreate()` 中处理；秒杀/拼团各有独立策略（由插件提供）
- **会员**：订单完成触发积分/成长值发放、钱包变更
- **运费**：各策略 `applyFreight()` 自行处理，普通订单委托 `FreightServiceInterface`
- **支付**：`DomainPayService` 支持微信支付和余额支付
