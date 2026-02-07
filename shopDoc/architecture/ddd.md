# DDD 架构实践

Mine Shop 以**领域驱动设计（DDD）**为核心方法论，通过战略设计划分业务疆域，战术设计落实到代码结构，形成「接口 → 应用 → 领域 → 基础设施」四层协作的高内聚体系。

## 战略设计：限界上下文

| 上下文 | 子域 | 职责 |
| ------ | ---- | ---- |
| **Catalog** | `Brand`、`Category`、`Product` | 商品、品牌、分类、SKU、规格、快照 |
| **Trade** | `Order`、`Payment`、`Shipping` | 订单、支付、运费、发货、履约 |
| **Marketing** | `Coupon`、`GroupBuy`、`Seckill` | 优惠券、团购、秒杀 |
| **Member** | — | 会员生命周期、钱包、积分、标签、地址 |
| **Auth** | — | 认证、Token、登录策略 |
| **Permission** | — | 角色、权限、菜单、数据权限 |
| **Organization** | — | 组织架构、部门 |
| **Infrastructure** | `Attachment`、`AuditLog`、`SystemSetting` | 附件、操作日志、系统设置 |

各上下文以统一语言描述模型，在接口层通过 DTO/Transformer 解耦，在领域层通过实体、值对象、服务与策略保持业务完整性。

## 目录结构

```
app/Domain/
├── Auth/           # Contract / Entity / Enum / Service / ValueObject
├── Catalog/
│   ├── Brand/
│   ├── Category/
│   └── Product/    # Entity / Service / Repository / Contract / Event / Mapper
├── Trade/
│   ├── Order/      # Entity / Service / Strategy / Factory / Repository / Contract / ValueObject
│   ├── Payment/    # DomainPayService / Enum
│   └── Shipping/   # Service / Repository / Mapper
├── Marketing/
│   ├── Coupon/
│   ├── GroupBuy/
│   └── Seckill/
├── Member/         # Entity / Service / Repository / Event / Listener / Mapper / ValueObject
├── Permission/     # Entity / Service / Repository / Event / Mapper / ValueObject
├── Organization/   # Contract / Repository / Service
└── Infrastructure/
    ├── Attachment/
    ├── AuditLog/
    └── SystemSetting/
```

## 战术设计：四层模型

```
Interface  ── Controller / Request / Middleware / Transformer
      ↓
Application ─ CommandService / QueryService / Mapper / Listener
      ↓
Domain ───── Entity / ValueObject / DomainService / Repository Interface / Strategy / Contract
      ↓
Infrastructure ─ ORM Model / Repository Impl / Cache / Lua / Command / Crontab
```

### Interface Layer

- HTTP 控制器、路由注解、请求验证（Request）、JWT + RBAC 中间件
- Transformer 负责 Entity → 前端响应的字段映射（camelCase）
- `Result` / `ResultCode` 统一响应格式

### Application Layer

按场景分组，Admin 和 Api 各自独立：

```
app/Application/
├── Admin/
│   ├── Catalog/        # 商品、品牌、分类管理
│   ├── Trade/          # 订单、发货管理
│   ├── Marketing/      # 优惠券、秒杀、团购管理
│   ├── Member/         # 会员管理
│   ├── Permission/     # 权限管理
│   ├── Organization/   # 组织管理
│   └── Infrastructure/ # 附件、设置、日志、Listener
└── Api/
    ├── Cart/           # 购物车
    ├── Coupon/         # 优惠券查询
    ├── Home/           # 首页聚合
    ├── Member/         # 会员中心、地址、认证
    ├── Order/          # 下单、支付
    ├── Payment/        # 支付编排
    └── Product/        # 商品查询
```

- `CommandService` 负责写操作与事务
- `QueryService` 负责查询、统计、缓存
- `Mapper` 在 DTO ⇄ Entity 之间转换
- `Listener` 订阅领域事件并触发副作用（如 `UserOperationSubscriber`）

### Domain Layer

- **Entity**：聚合根（`OrderEntity`、`MemberEntity`、`ProductEntity`）
- **Value Object**：不可变对象（`OrderAddressValue`、`OrderPriceValue`）
- **Domain Service**：跨实体业务（`DomainOrderStockService`、`DomainPayService`）
- **Repository Interface**：持久化契约
- **Strategy / Factory**：订单类型策略、优惠券策略
- **Contract**：输入契约接口（`OrderPreviewInput`、`OrderSubmitInput`）
- **Domain Event**：`OrderCreatedEvent`、`MemberBalanceAdjusted`、`ProductStockWarningEvent`

### Infrastructure Layer

- ORM 模型（`App\Infrastructure\Model`）
- Repository 实现
- Redis / Lua 脚本（库存扣减）
- CLI 命令 / Crontab
- 第三方适配（yansongda/pay、Geo 同步）

## 领域建模要点

- **聚合根**：订单、会员、优惠券、团购等，每个聚合根提供一致性边界
- **值对象**：`OrderAddressValue`、`OrderPriceValue`（所有金额为 int 分）、`GeoPathValue` 等封装不可变数据
- **领域事件**：在 Domain Service 内触发，Listener 运行在 Application 或 Infrastructure 层
- **数据权限**：`DataScope` 以 Attribute + AOP 方式织入 Query，属于基础设施横切增强

## 跨层规则

1. **Domain 层禁止依赖 Application / Interface**：领域层只依赖自身和 Infrastructure 抽象接口
2. **Application 层编排领域服务**：事务、事件调度、DTO 转换在此层完成
3. **Interface 层只做协议适配**：参数验证、权限校验、响应格式化
4. **Infrastructure 层实现技术细节**：ORM、缓存、外部 SDK

## FAQ

- **为什么保留 Repository Interface？** 便于测试与替换实现（如引入 ES），应用层仅依赖接口
- **事件太多会不会难以追踪？** 命名规范 + 链路日志 + 操作日志记录调用链
- **如何扩展新业务？** 添加新的子域目录，遵守四层结构，在 Application 层提供独立 Service 即可

下一步阅读 [分层设计](/architecture/layers) 与 [设计模式](/architecture/patterns)。
