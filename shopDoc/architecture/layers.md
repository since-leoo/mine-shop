# 分层设计

Mine Shop 的工程代码严格遵循「接口 → 应用 → 领域 → 基础设施」四层架构。此文档从职责、目录、示例代码与跨层交互四个角度展开说明。

```
Interface (HTTP/CLI) ─► Application (CQRS/Orchestration) ─► Domain (Model/Service/Event) ─► Infrastructure (ORM/Cache/Lua)
```

## Interface Layer — 接口层

**职责**：

- 接收并验证请求（HTTP、CLI、WebSocket 等）。
- 注入认证、权限、数据权限、操作日志等横切逻辑。
- 格式化响应（统一 Result、错误码、多语言文案）。

**目录**：`app/Interface/Admin|Api|Common`

**示例**：会员概览控制器摘录

```php
#[GetMapping(path: 'overview')]
public function overview(MemberRequest $request): Result
{
    $filters = $request->validated();
    return $this->success($this->queryService->overview($filters));
}
```

## Application Layer — 应用层

**职责**：

- 实现 CQRS：CommandService 负责写、QueryService 负责读。
- 事件编排、事务控制、DTO/VO 与实体互转。
- 聚合跨领域（如订单创建需同时校验库存、优惠券、会员）。

**目录**：`app/Application/*`

**关键组件**：

- `CommandService`：写操作 + 事务提交。
- `QueryService`：查询、统计、缓存、分页。
- `Mapper`：数据装配（`Array → Entity` / `Entity → Array`）。
- `Event Handler`：订阅领域事件并触发外部副作用。

**示例**：`MemberQueryService::overview`

```php
final class MemberQueryService implements MemberQueryInterface
{
    public function __construct(private readonly MemberService $memberService) {}

    public function overview(array $filters): array
    {
        return $this->memberService->overview($filters);
    }
}
```

## Domain Layer — 领域层

**职责**：封装业务规则，是唯一可以修改业务状态的地方。

**组成**：

- **Entity**：聚合根（订单、会员、团购活动等）。
- **Value Object**：不可变对象（地址、价格、地区路径）。
- **Domain Service**：跨实体业务（库存锁定、团购成团）。
- **Repository Interface**：持久化契约（如 `MemberRepository::overview`）。
- **Strategy / Factory**：扩展点（订单类型策略、优惠券工厂）。
- **Domain Event**：`OrderCreatedEvent`、`MemberBalanceAdjusted` 等。

**示例**：地区分布统计（领域层）

```php
private function buildRegionBreakdown(Builder $query): array
{
    return (clone $query)
        ->selectRaw("COALESCE(NULLIF(province, ''), '未填写地区') as region_key, COUNT(*) as total")
        ->groupBy('region_key')
        ->orderByDesc('total')
        ->limit(6)
        ->get()
        ->map(fn ($row) => [
            'key' => (string) $row->region_key,
            'label' => (string) $row->region_key,
            'value' => (int) $row->total,
        ])->toArray();
}
```

## Infrastructure Layer — 基础设施层

**职责**：为领域层提供技术能力，包括 ORM、缓存、消息、Lua、CLI、第三方 SDK 等。领域层只依赖接口，具体实现全部下沉到该层。

**组成**：

- **Model**：Hyperf ORM 模型（`App\Infrastructure\Model`）。
- **Repository Impl**：实现领域仓储接口。
- **Cache / Redis / Lua**：库存脚本、分布式锁、热门数据缓存。
- **Command / Crontab**：`mall:sync-regions`、`system:update`。
- **Service Adapter**：支付、对象存储、Geo 地址同步、短信等第三方接入。

**示例**：Geo 同步命令（CLI）

```php
#[Command]
class SyncGeoRegionsCommand extends HyperfCommand
{
    public function __construct(
        protected ContainerInterface $container,
        private readonly GeoRegionSyncService $syncService,
    ) {
        parent::__construct('mall:sync-regions');
    }

    public function handle()
    {
        $summary = $this->syncService->sync([...]);
        $this->info(sprintf('同步完成，写入 %d 条记录', $summary['records'] ?? 0));
    }
}
```

## 横切关注点

| 能力 | 实现位置 | 说明 |
| ---- | -------- | ---- |
| JWT 认证 | Interface Middleware | 解码 Token、注入 User Context |
| RBAC + 数据权限 | Interface + Infrastructure (AOP) | `PermissionMiddleware` + `DataScope` 注解 |
| 事务 | Application Layer | CommandService 以 `db->transaction()` 包裹 |
| 日志 & 审计 | Interface / Domain Event | 操作日志、用户登录日志 |
| 缓存 | Application / Infrastructure | QueryService 使用 CacheInterface，Geo Tree 使用 PSR-16 缓存 |
| Lua / 分布式锁 | Infrastructure | `lock_and_decrement.lua`、`rollback.lua` |

## 开发建议

1. **接口层避免直接操作模型**，改由应用层服务注入。
2. **新增业务** 先定义领域实体与仓储接口，再补基础设施实现。
3. **查询性能**：QueryService 可选择 `->remember()` 或 PSR-16 缓存，失效策略放在应用层。
4. **事件追踪**：领域事件命名 `模块:动作`，监听器位于 `app/Domain/**/Listener` 或 `app/Infrastructure/**/Listener`。
5. **脚本/任务**：命令行位于 `app/Infrastructure/Command`，Crontab 配置在 `config/autoload/crontab.php`。

掌握分层职责后，可前往 [DDD 架构](/architecture/ddd) 与 [设计模式](/architecture/patterns) 阅读更深入的原理与实现。*** End Patch
