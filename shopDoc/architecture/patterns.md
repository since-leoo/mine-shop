# 设计模式

Mine Shop 系统化应用经典设计模式，保持代码的可读性、扩展性与可测试性。

## 总览

| 模式 | 用途 | 代表代码 |
| ---- | ---- | -------- |
| **Repository** | 隔离持久化实现 | `App\Domain\*\Repository\*Repository` |
| **Strategy** | 可插拔业务策略 | `OrderTypeStrategyInterface` |
| **Factory** | 策略分发 + 动态注册 | `OrderTypeStrategyFactory` |
| **CQRS** | 读写分离 | `*CommandService` / `*QueryService` |
| **Mapper** | DTO ↔ Entity 转换 | `Application/*/Mapper`、`Domain/*/Mapper` |
| **Event / Observer** | 领域事件、异步解耦 | `Domain/**/Event` + `Application/**/Listener` |
| **Template Method** | 统一流程骨架 | `AttachmentService` 上传流程 |
| **Decorator** | 组合能力 | `DataScope` + `PermissionMiddleware` |
| **Builder** | 构建复杂实体 | `OrderPreviewInput` → `OrderEntity` |
| **Adapter** | 插件接口适配 | `CouponServiceAdapter`、`FreightServiceAdapter` |

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
    public function applyFreight(OrderEntity $orderEntity): void;
    public function applyCoupon(OrderEntity $orderEntity, ?int $couponId): void;
    public function rehydrate(OrderEntity $orderEntity, ContainerInterface $container): void;
    public function postCreate(OrderEntity $orderEntity): void;
}
```

`OrderTypeStrategyFactory` 内置注册 `NormalOrderStrategy`，`SeckillOrderStrategy` 和 `GroupBuyOrderStrategy` 由各自插件在 `boot()` 时动态注册。下单流程根据 `order_type` 选择策略：

```php
$strategy = $this->strategyFactory->make($entity->getOrderType());
$strategy->validate($entity);
$strategy->buildDraft($entity);
$strategy->applyFreight($entity);              // 运费（策略自行处理）
$strategy->applyCoupon($entity, $couponId);    // 优惠券（一次一张）
```

新增订单玩法只需新增策略类并在插件 `boot()` 中注册，无需修改主应用代码。

### 运费与优惠券下沉到策略

运费计算和优惠券应用已从 `DomainApiOrderCommandService` 下沉到各策略的 `applyFreight()` 和 `applyCoupon()` 方法中。每种订单类型可以有完全不同的运费和优惠逻辑：

- **普通订单**：通过 `FreightServiceInterface` 调用运费插件计算，通过 `CouponServiceInterface` 调用优惠券插件
- **秒杀订单**：通常包邮，不支持优惠券
- **拼团订单**：可能有特殊运费规则，不支持优惠券叠加

### 异步 Job 中的 rehydrate

订单提交采用异步架构，同步阶段只做校验和库存预占，异步 `OrderCreateJob` 负责入库。`rehydrate()` 方法在异步 Job 中被调用，让各策略从快照恢复所需的活动实体（如秒杀场次、拼团活动），供 `postCreate()` 使用。

### 优惠券策略

`NormalOrderStrategy.applyCoupon()` 验证优惠券归属、状态、有效期、满减门槛，支持 `fixed`（面值直减）和 `percent`（折扣）两种类型，金额单位为分。一次只能使用一张优惠券。

## 插件适配器模式

主应用定义接口契约，插件提供适配器实现，通过 ConfigProvider 的 `dependencies` 注册绑定到 Hyperf DI 容器：

```php
// 主应用定义接口
interface CouponServiceInterface {
    public function findUsableCoupon(int $memberId, int $couponId): ?array;
    public function settleCoupon(int $couponUserId, int $orderId): void;
}

// 插件提供适配器
class CouponServiceAdapter implements CouponServiceInterface { /* ... */ }

// 插件 ConfigProvider 注册绑定
'dependencies' => [
    CouponServiceInterface::class => CouponServiceAdapter::class,
]
```

当前接口契约：

| 接口 | 插件适配器 | 用途 |
|------|-----------|------|
| `CouponServiceInterface` | coupon 插件 `CouponServiceAdapter` | 优惠券查询与核销 |
| `FreightServiceInterface` | shipping 插件 `FreightServiceAdapter` | 运费计算 |

详见 [插件系统](./plugin-system.md)。

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

1. **新增玩法**：新增策略类 + 插件 `boot()` 注册，零侵入主应用
2. **扩展数据源**：通过 Repository 接口实现多存储
3. **可测试性**：保持接口与实现的依赖倒置，Command 与 Query 可分离测试
4. **模块解耦**：通过接口契约 + 插件适配器模式，实现业务模块热插拔
5. **观察可见性**：事件命名统一，监听器中加入结构化日志

了解常用模式后，可继续阅读 [插件系统](/architecture/plugin-system)、[DDD 架构](/architecture/ddd) 与 [分层设计](/architecture/layers)。
