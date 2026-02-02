# DDD 架构实践

Mine Shop 自创建伊始即以 **领域驱动设计（Domain-Driven Design，DDD）** 为核心方法论。通过战略设计划分业务疆域，战术设计落实到代码结构，最终形成「接口 → 应用 → 领域 → 基础设施」四层协作的高内聚体系。

## 战略设计：业务边界

| Bounded Context | 职责 | 代表模块 |
| --------------- | ---- | -------- |
| **Commerce** | 商品、订单、库存、支付、履约 | `Product`, `Order`, `Stock`, `Payment` |
| **Marketing** | 秒杀、团购、优惠券、活动联动 | `Seckill`, `GroupBuy`, `Coupon` |
| **Member** | 会员生命周期、钱包、积分、标签、概览 | `Member`, `MemberWallet`, `MemberTag` |
| **Geo** | 四级地区库、地理联动、搜索服务 | `GeoRegion`, `GeoQueryService`, `/geo/*` |
| **Platform** | 权限、组织、设置、附件、操作日志 | `Permission`, `SystemSetting`, `Attachment` |

各上下文以 **统一语言** 描述模型，在接口层通过 DTO/VO 解耦，在领域层通过实体、值对象、服务与策略保持业务完整性。

## 战术设计：四层模型

```
Interface  ── Controller / Request / Middleware / VO
      ↓
Application ─ CommandService / QueryService / Assembler / Event Handler
      ↓
Domain ───── Entity / ValueObject / DomainService / Repository Interface / Strategy
      ↓
Infrastructure ─ ORM Model / Repository Impl / Cache / Queue / Lua / Command / Geo Sync
```

### Interface Layer

- **协议适配**：HTTP 控制器、路由、请求验证、JWT + RBAC 中间件。
- **用户体验**：将复杂查询（如会员概览、地区构成）整理为易用参数。
- **统一响应**：`Result` / `ResultCode` 保证外部接口稳定。

### Application Layer

- **CQRS**：`CommandService` 负责写操作与事务，`QueryService` 负责查询、统计、缓存。
- **Assembler**：在 DTO ⇄ Entity 之间转换，避免 Controller 与 Domain 直接耦合。
- **流程编排**：如创建团购活动会依次校验 SKU、库存、时间窗并触发事件。
- **事件调度**：通过 Hyperf 事件系统分发 `MemberBalanceAdjusted`、`OrderCreated` 等领域事件。

### Domain Layer

- **Entity & Value Object**：`OrderEntity`、`OrderItemEntity`、`OrderAddressValue` 等负责业务规则。
- **Domain Service**：`OrderService`、`MemberService` 等聚合跨实体的业务动作。
- **Repository Interface**：定义持久化契约（如 `MemberRepository::overview` 计算地区分布）。
- **策略/工厂**：订单类型策略、优惠券发放策略、团购成团策略等。
- **领域事件**：以 `Event` + `Listener` 形式异步解耦库存回滚、日志记录、钱包流水。

### Infrastructure Layer

- **持久化**：Hyperf ORM 模型、Query Builder、数据库迁移。
- **缓存与消息**：Redis、Lua 脚本（`lock_and_decrement.lua`、`rollback.lua`）、事件队列。
- **命令/调度**：`mall:sync-regions`、`system:check-tables`、Crontab 定时任务。
- **外部服务**：支付（yansongda/pay）、附件、短信、Geo 地址库同步。

## 数据流示例：会员概览（地区构成）

1. `GET /admin/member/member/overview` → `MemberController@overview` 解析筛选条件。
2. `MemberQueryService::overview` 调用 `MemberService::overview`（应用层）。
3. `MemberRepository::overview` 在领域层执行 Query Builder：
   - `buildTrendSeries()` 输出新增/活跃曲线（最近 N 天）。
   - `buildRegionBreakdown()` 聚合 `province` 字段，缺省值归为「未填写地区」。
   - `buildLevelBreakdown()` 统计等级分布。
4. 结果返回给前端，`/web/src/modules/member/views/overview/index.vue` 使用 `useEcharts` 绘制折线 + 饼图 + 进度条。
5. 若地区信息由 Geo 模块更新，通过 `member.region_path`、`/geo/pcas` 级联选择保障一致性。

此流程体现：接口层只负责参数与权限，应用层编排与聚合，领域层掌控规则，基础设施层负责 SQL 与缓存。

## 领域建模要点

- **聚合根**：订单、会员、优惠券、团购、地区版本等，每个聚合根提供一致性边界。
- **值对象**：`OrderAddressValue`、`OrderLogValue`、`GeoPathValue` 等封装不可变数据。
- **领域事件**：`OrderCreatedEvent`、`MemberBalanceAdjusted`、`ProductStockWarningEvent` 等在领域层触发，监听器运行在基础设施层。
- **数据权限**：`DataScope` 以 Attribute + AOP 方式织入 Query，属于基础设施对领域的横切增强。

## 与基础设施的协作

- **Geo 地址库**：`GeoRegionSyncService` 负责下载、校验、写入 `geo_regions` / `geo_region_versions` 表，并缓存到 `CacheInterface`。领域层只依赖 `GeoQueryService` 暴露的 `getCascadeTree()`/`search()`，无需感知数据来源。
- **库存系统**：`OrderStockService` 使用 Lua 原子扣减，成功后再由领域服务写库；失败则抛出业务异常并在应用层回滚。
- **支付与网关**：领域层依赖 `PaymentService` 接口，具体由基础设施封装 yansongda/pay。

## FAQ

- **为什么保留 Repository Interface？** 便于测试与替换实现（如引入 ES、ClickHouse），并保持应用层仅依赖接口。
- **事件太多会不会难以追踪？** 我们使用命名规范（`模块:动词`）、链路日志、以及 Hyperf 自带的事件监听，另有操作日志记录调用链。
- **如何扩展新的业务形态？** 添加新的 Bounded Context 或聚合根，遵守 4 层结构，并在应用层提供独立 Command/Query Service，即可无痛扩展。

下一步建议阅读 [分层设计](/architecture/layers) 与 [设计模式](/architecture/patterns) 以获得更细节的实现视角。*** End Patch
