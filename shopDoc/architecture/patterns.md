# 设计模式

Mine Shop 系统化应用经典设计模式，保持代码的可读性、扩展性与可测试性。

## 总览

| 模式 | 用途 | 代表代码 |
| ---- | ---- | -------- |
| **Repository** | 隔离持久化实现 | `App\Domain\*\Repository\*Repository` |
| **Strategy** | 可插拔业务策略 | `OrderTypeStrategyInterface` |
| **Factory** | 策略分发 | `OrderTypeStrategyFactory` |
| **CQRS** | 读写分离 | `*CommandService` / `*QueryService` |
| **Mapper** | DTO ↔ Entity 转换 | `Application/*/Mapper`、`Domain/*/Mapper` |
| **Event / Observer** | 领域事件、异步解耦 | `Domain/**/Event` + `Application/**/Listener` |
| **Template Method** | 统一流程骨架 | `AttachmentService` 上传流程 |
| **Decorator** | 组合能力 | `DataScope` + `PermissionMiddleware` |
| **Builder** | 构建复杂实体 | `OrderPreviewInput` → `OrderEntity` |

## Repository 模式

- 领域层只面向接口（如 `OrderRepository`、`MemberRepository`），便于切换 MySQL / ES / 缓存
- 基础设施层通过 ORM 或 Query Builder 实现
- 应用层注入接口即可 Mock，测试更轻松

## Strategy + Factory

### 订单类型策略

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

`OrderTypeStrategyFactory` 注册 `NormalOrderStrategy`、`SeckillOrderStrategy`、`GroupBuyOrderStrategy`。下单流程根据 `order_type` 选择策略：

```php
$strategy = $this->strategyFactory->make($entity->getOrderType());
$strategy->validate($entity);
$strategy->buildDraft($entity);
$this->applyFreight($entity);           // 运费计算
$strategy->applyCoupon($entity, $couponList);  // 优惠券
$strategy->adjustPrice($entity);         // 价格调整
```

新增订单玩法只需新增策略类并在工厂注入，无需侵入既有逻辑。

### 优惠券策略

`NormalOrderStrategy.applyCoupon()` 验证优惠券归属、状态、有效期、满减门槛，支持 `fixed`（面值直减）和 `percent`（折扣）两种类型，金额单位为分。

## CQRS + Mapper

- `CommandService`：处理事务与写操作。如 `AppApiOrderCommandService` 编排预览/提交/取消/确认收货
- `QueryService`：负责列表、统计、缓存。如 `AppApiOrderQueryService` 提供订单列表/详情/统计
- `Mapper`：输入是数组/DTO，输出是实体；反向将实体转换为 API 需要的数据结构。`OrderMapper::getNewEntity()` 创建新订单实体，`OrderMapper::fromModel()` 从 ORM 模型还原实体

## 领域事件（Event）

- **触发**：领域状态变化时在 Domain Service 内触发（如 `MemberBalanceAdjusted`、`ProductStockWarningEvent`）
- **监听**：Listener 放在 `Application/**/Listener` 或 `Infrastructure/**/Listener`
  - `UserOperationSubscriber`（Application 层）：记录操作日志
  - `ProductSnapshotListener`（Domain 层）：商品快照缓存
- **原则**：Domain 层触发事件，Application 层处理副作用，保持领域纯净

## 模板方法 / 装饰器

- **模板方法**：附件上传等场景，公共流程写在抽象类中，关键步骤由子类实现
- **装饰器**：数据权限在 `DataScope` Attribute 里定义范围，AOP 切面在执行查询前注入额外条件

## Builder

`OrderPreviewInput` / `OrderSubmitInput` 契约接口封装下单入参，`DomainApiOrderCommandService::buildEntityFromInput()` 将其转换为 `OrderEntity`，包含地址解析、商品项初始化等构建逻辑。

## 演进建议

1. **新增玩法**：优先考虑策略 + 工厂组合，避免 if-else 失控
2. **扩展数据源**：通过 Repository 接口实现多存储
3. **可测试性**：保持接口与实现的依赖倒置，Command 与 Query 可分离测试
4. **观察可见性**：事件命名统一，监听器中加入结构化日志

了解常用模式后，可继续阅读 [DDD 架构](/architecture/ddd) 与 [分层设计](/architecture/layers)。
