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
| `order_no` | string | 业务幂等键，由 `OrderRepository::save()` 生成 |
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

不同订单类型（普通、秒杀、团购）差异在验证逻辑、商品构建、优惠券、价格调整、后置处理。使用策略模式解耦：

```php
interface OrderTypeStrategyInterface
{
    public function type(): string;
    public function validate(OrderEntity $orderEntity): void;
    public function buildDraft(OrderEntity $orderEntity): OrderEntity;
    public function applyCoupon(OrderEntity $orderEntity, array $couponList): void;
    public function adjustPrice(OrderEntity $orderEntity): void;
    public function postCreate(OrderEntity $orderEntity): void;
}
```

`OrderTypeStrategyFactory` 注册 `NormalOrderStrategy`、`SeckillOrderStrategy`、`GroupBuyOrderStrategy`。

## 下单流程

由 `DomainApiOrderCommandService::submit()` 编排，完整步骤：

```
1. buildEntityFromInput()        ← 从 OrderSubmitInput 构建 Entity + 解析地址
2. guardPreorderAllowed()        ← 预售商品检查
3. applySubmissionPolicy()       ← 设置订单过期时间（系统配置）
4. strategy.validate()           ← 策略验证（会员、商品项）
5. strategy.buildDraft()         ← 构建订单草稿（查快照、校验上下架、计算商品金额）
6. applyFreight()                ← 运费计算（FreightCalculationService）
7. strategy.applyCoupon()        ← 优惠券验证与折扣计算
8. strategy.adjustPrice()        ← 价格调整（普通订单直通）
9. entity.verifyPrice()          ← 校验前端传入金额与计算金额一致
10. stockService.acquireLocks()  ← 获取 SKU 分布式锁
11. stockService.reserve()       ← Redis Lua 原子扣减库存
12. repository.save()            ← 持久化订单（生成 order_no）
13. markCouponsUsed()            ← 标记优惠券已使用
14. strategy.postCreate()        ← 后置处理
15. stockService.releaseLocks()  ← 释放锁（finally）
```

若 `repository.save()` 失败，立即调用 `stockService.rollback()` 归还库存。

### 运费计算

`FreightCalculationService.calculate()` 按商品维度计算运费：

- `free`：包邮
- `flat`：固定运费
- `template`：运费模板（按重量/件数/体积阶梯计费）
- `default`：使用系统默认运费配置

支持偏远地区加价。运费写入 `OrderPriceValue.shippingFee`。

### 预览流程

`preview()` 与 `submit()` 共享前 8 步（不扣库存、不持久化），返回金额明细和可用优惠券，供前端展示确认页。

## 流程图

```
[OrderSubmitInput]
      ↓ buildEntityFromInput + 地址解析
[OrderEntity Draft]
      ↓ validate → buildDraft → applyFreight → applyCoupon → adjustPrice
[价格校验] ──不一致→ 抛错
      ↓
[获取锁 → 库存预占] ──失败→ 回滚并抛错
      ↓ 成功
[Repository.save()] ──失败→ 库存回滚
      ↓
标记优惠券 → postCreate → 释放锁
      ↓
等待支付 → 支付成功 → 发货 → 完成
```

## 关键扩展点

| 扩展点 | 说明 |
| ------ | ---- |
| `OrderTypeStrategyInterface` | 新增订单类型（预售、订阅等） |
| `FreightCalculationService` | 运费规则扩展（运费模板、偏远地区） |
| `DomainOrderStockService` | 可替换为消息队列或分库方案 |
| 领域事件 | `OrderCreated`、`OrderPaid`、`OrderCancelled` 等监听器可扩展 |
| `OrderRepository` | 若需 ES/OLAP 查询，可新增读模型实现 |

## 与其他模块的交互

- **库存**：订单提交 → Lua 扣减；取消/失败 → 回滚
- **营销**：优惠券核销在策略层处理；秒杀/团购各有独立策略
- **会员**：订单完成触发积分/成长值发放、钱包变更
- **运费**：`FreightCalculationService` 按商品运费配置 + 收货省份计算
- **支付**：`DomainPayService` 支持微信支付和余额支付
