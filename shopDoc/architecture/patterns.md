# 设计模式

Mine Shop 在多个上下文中系统化应用经典设计模式，以保持代码的可读性、扩展性与可测试性。本页按模式介绍使用场景、示例位置与演进建议。

## 总览

| 模式 | 用途 | 代表代码 |
| ---- | ---- | -------- |
| **Repository** | 隔离持久化实现 | `App\Domain\*\Repository\*Repository` |
| **Strategy** | 可插拔业务策略 | `OrderTypeStrategyInterface`、`CouponStrategyInterface` |
| **Factory** | 复杂对象构造/策略分发 | `OrderTypeStrategyFactory`, `CouponFactory` |
| **CQRS** | 读写分离 | `Application/*/Service/*CommandService` |
| **Mapper** | DTO ↔ Entity 转换 | `Application/*/Mapper` |
| **Event / Observer** | 领域事件、异步解耦 | `app/Domain/**/Event` + `Listener` |
| **Template Method** | 统一流程骨架 | `AttachmentService` 上传流程 |
| **Decorator** | 组合能力 | `DataScope` + `PermissionMiddleware` |
| **Builder** | 构建复杂实体 | `OrderPreviewInput` / `OrderSubmitInput` → `OrderEntity` |

## Repository 模式

- **目的**：让领域层只面向接口，便于切换 MySQL / ES / 缓存。
- **实现**：`App\Domain\Member\Repository\MemberRepository` 定义 `overview()`，基础设施层通过 Query Builder 或 ORM 实现。
- **收益**：应用层注入接口即可 Mock，测试更轻松；跨数据库迁移也更安全。

## Strategy + Factory

### 订单类型策略

```php
interface OrderTypeStrategyInterface
{
    public function type(): string;
    public function validate(OrderEntity $entity): void;
    public function buildDraft(array $data): OrderEntity;
    public function postCreate(OrderEntity $entity): void;
}
```

`OrderTypeStrategyFactory` 在构造函数中注册 `NormalOrderStrategy`、`SeckillOrderStrategy`、`GroupBuyOrderStrategy`，`OrderService` 只需根据 `order_type` 取对应策略，即可扩展新玩法。

### 优惠券/团购策略

同样模式应用于优惠券核销、团购成团规则（最低成团人数、超时退款等）。

## CQRS + Mapper

- `CommandService`：处理事务与写操作。例如 `CouponCommandService` 新建优惠券时调用 Mapper 生成实体，并委派给 Domain Service。
- `QueryService`：负责列表、统计、缓存。如 `MemberQueryService::overview()` 会调用 Repository 的 `buildTrendSeries`、`buildRegionBreakdown`。
- `Mapper`：输入是数组/DTO，输出是实体；反向将实体转换为 API 需要的数据结构。

## 领域事件（Event）

- **触发**：领域状态变化时（创建订单、调整余额、同步地址）在 Domain Service 内触发事件。
- **监听**：Listener 放在 `app/Domain/**/Listener` 或 `app/Infrastructure/**/Listener`，执行日志记录、通知、队列推送等副作用。
- **例子**：`MemberBalanceAdjusted` → `RecordMemberBalanceLogListener`，`OrderCreatedEvent` → `OrderStatusNotifyListener`。

## 模板方法 / 装饰器

- **模板方法**：附件上传、导入导出等场景，公共流程写在抽象类中，关键步骤由子类实现。
- **装饰器**：数据权限在 `DataScope` Attribute 里定义范围，切面（AOP）在执行查询前注入额外条件，实现动态过滤。

## Builder

`OrderPreviewInput` / `OrderSubmitInput` 契约接口封装下单入参，`OrderService::buildEntityFromInput()` 将其转换为 `OrderEntity`、`OrderItemEntity`、`OrderAddressValue` 等，保证字段完整性与校验集中。

## 演进建议

1. **新增玩法**：优先考虑策略 + 工厂组合，避免 if-else 失控。
2. **扩展数据源**：通过 Repository 接口实现多存储；必要时在应用层引入缓存装饰器。
3. **可测试性**：保持接口与实现的依赖倒置，命令与查询可以单测/集成测试分离。
4. **观察可见性**：事件命名统一，监听器中加入结构化日志，方便排查生产问题。

了解常用模式后，可继续阅读 [DDD 架构](/architecture/ddd) 与 [分层设计](/architecture/layers) 获取更宏观的视角。*** End Patch
