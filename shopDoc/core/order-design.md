# 订单系统设计

订单是 Mine Shop 的核心聚合根，承担商品、库存、营销、会员资产与履约模块之间的纽带。本页围绕领域模型、状态机、类型策略、流程编排以及扩展点进行阐述。

## 领域模型

```
OrderEntity
├── OrderItemEntity[]      // SKU 快照、单价、数量、优惠信息
├── OrderAddressValue      // 收货地址快照（含 region_path）
├── OrderPriceValue        // 金额拆解：商品/运费/优惠/实付
└── OrderLogValue[]        // 状态日志、操作者、备注
```

**关键字段**：

| 字段 | 说明 |
| ---- | ---- |
| `order_no` | 业务幂等键，由 `OrderFactory` 生成 |
| `member_id` | 会员 ID，外键指向 `members` |
| `order_type` | `normal` / `seckill` / `group_buy` 等 |
| `status` | 订单状态（待支付→已支付→已发货→已完成/取消/退款） |
| `pay_status` / `shipping_status` | 与订单状态并行的精细状态 |
| `goods_amount` / `shipping_fee` / `discount_amount` / `pay_amount` | 金额拆解 |
| `price_detail` | JSON，保存优惠券、积分、余额抵扣等明细 |
| `extras` | JSON 扩展字段，例如渠道、端类型 |

## 状态机

```
PENDING (待支付)
  ├─ 支付成功 → PAID
  │     └─ 发货 → SHIPPED → 确认收货 → COMPLETED
  └─ 超时/取消 → CANCELLED

PAID
  └─ 退款 → REFUNDED
```

`pay_status`、`shipping_status` 作为并行状态：

- `pay_status`: `pending` / `paid` / `failed` / `refunded`
- `shipping_status`: `pending` / `partial_shipped` / `shipped` / `delivered`

状态变更由 `OrderService` 集中处理，并通过 `OrderStatusNotifyListener` 记录日志、推送通知。

## 订单类型策略

不同订单类型（普通、秒杀、团购）差异主要在**验证逻辑**、**库存扣减**、**后置处理**。使用策略模式解耦：

```php
interface OrderTypeStrategyInterface
{
    public function type(): string;
    public function validate(OrderEntity $entity): void;
    public function buildDraft(array $payload): OrderEntity;
    public function postCreate(OrderEntity $entity): void;
}
```

`OrderTypeStrategyFactory` 在构造函数中注册已支持的策略，`OrderService` 根据 `order_type` 选择对应实现：

```php
$strategy = $this->strategyFactory->make($command->getOrderType());
$draft = $strategy->buildDraft($command->toArray());
$strategy->validate($draft);
$order = $this->repository->save($draft);
$strategy->postCreate($order);
```

新增订单玩法时仅需新增策略类并在工厂注入，无需侵入既有逻辑。

## 下单流程

1. **构建提交命令**：前端将购物车/立即购买数据整理为 `OrderSubmitCommand`，包含 SKU、数量、优惠、地址、remark 等。
2. **Mapper 转换**：`OrderAssembler` 根据命令创建 `OrderEntity`、`OrderItemEntity`、`OrderAddressValue`、金额值对象。
3. **库存预扣**：`OrderStockService` 基于 Redis + Lua (`lock_and_decrement.lua`) 原子扣减库存，并返回结果。
4. **保存订单**：`OrderRepository->save()`；若失败触发库存回滚脚本 (`rollback.lua`)。
5. **触发事件**：`OrderCreatedEvent` 通知营销、积分、日志、异步任务等。
6. **支付**：通过 `PaymentService` 调用 yansongda/pay，对接支付宝/微信/余额。
7. **履约**：发货、物流、确认收货等操作会更新状态并写入 `OrderLogValue`。

## 金额与优惠

所有金额相关逻辑通过 `OrderPriceValue` 管理，确保精度与可追溯性：

```php
class OrderPriceValue
{
    public function __construct(
        public float $goodsAmount,
        public float $shippingFee,
        public float $discountAmount,
        public float $pointsAmount,
        public float $balanceAmount,
        public float $payAmount,
    ) {}
}
```

Mapper 会在预览/提交阶段计算金额，并持久化到 `price_detail` 字段，以便对账与售后使用。

## 日志与幂等

- **OrderLogValue**：记录每次状态变化与操作者（会员/系统/管理员）。
- **幂等**：`order_no` + `member_id` 可作为唯一键，防止重复创建；外部支付回调也以 `order_no` 为幂等键。

## 关键扩展点

| 扩展点 | 说明 |
| ------ | ---- |
| `OrderSubmitCommand` | 可扩展渠道、端类型、活动 ID、业务标记等 |
| `OrderTypeStrategyInterface` | 新增订单类型（如预售、订阅） |
| `OrderStockService` | 允许替换为消息队列、分库库表方案 |
| 领域事件 | `OrderCreated`, `OrderPaid`, `OrderCancelled` 等监听器可扩展通知、积分、CRM |
| `OrderRepository` | 若需 ES/OLAP 查询，可新增读模型实现 |

## 与其他模块的交互

- **库存模块**：订单提交 → Lua 扣减；取消/失败 → Lua 回滚；发货/完成 → 可与 WMS 对接。
- **营销模块**：优惠券核销、团购成团、秒杀限购在策略层处理并回写订单。
- **会员模块**：订单完成触发成长值/积分发放、钱包变更。
- **Geo 地址**：`OrderAddressValue` 存储 `region_path` 与快照，确保历史订单不受地址变动影响。
- **支付模块**：`PaymentService` 按支付方式生成参数，回调更新 `pay_status` 并触发相关事件。

## 流程图

```
[OrderSubmitCommand]
      ↓ Mapper
[OrderEntity Draft]
      ↓ Strategy.validate()
[库存预占] ──失败→ 回滚并抛错
      ↓ 成功
[Repository.save()]
      ↓
发布 OrderCreatedEvent
      ↓
等待支付 → 支付成功 → OrderPaidEvent → 发货 → 完成
```

通过上述设计，订单系统可以在保持清晰职责的同时，灵活扩展新的业务玩法并支撑高并发写入。*** End Patch
