# DDD 分层规范

## 层级依赖规则

```
Interface → Application → Domain ← Infrastructure
```

| 规则 | 说明 |
|------|------|
| Interface 只依赖 Application | 控制器注入 AppService，不直接调用 Domain |
| Application 编排 Domain | 组合多个领域服务，管理事务边界 |
| Domain 不依赖上层 | 不 import Interface / Application 的任何类 |
| Domain 通过 Contract 解耦 Infrastructure | 接口定义在 Domain，实现在 Infrastructure |
| Infrastructure 实现 Domain 契约 | ORM Model、外部服务适配器 |

## Domain 层内部结构

每个领域模块遵循统一结构：

```
Domain/{BoundedContext}/
├── Entity/          # 领域实体 — 承载状态和行为
├── ValueObject/     # 值对象 — 不可变的属性组合
├── Enum/            # 枚举 — 状态、类型等
├── Contract/        # 契约接口 — 定义依赖抽象
├── Service/         # 领域服务 — 编排实体操作
├── Repository/      # 仓储 — 持久化抽象
├── Mapper/          # 映射器 — Model ↔ Entity 转换
├── Event/           # 领域事件
├── Listener/        # 事件监听
├── Trait/           # 实体行为拆分
├── Strategy/        # 策略模式实现
├── Factory/         # 工厂
└── Api/             # 面向 API 端的领域服务
    ├── Command/     # 写操作
    └── Query/       # 读操作
```

## Entity 设计原则

- Entity 是领域核心，封装业务规则和状态变更
- 状态变更必须通过行为方法 (如 `markPaid()`, `cancel()`, `ship()`)
- 行为方法内部做状态守卫检查，拒绝非法状态转换
- Entity 不直接依赖 ORM Model，通过 Mapper 转换

## Mapper 职责

- `fromModel(Model $model): Entity` — ORM 模型转领域实体
- `getNewEntity(): Entity` — 创建空实体
- `toArray(Entity $entity): array` — 实体转数组 (用于持久化)

## Repository 职责

- 封装数据访问，对外暴露领域语义方法
- 内部使用 ORM Model 查询
- 返回 Entity 或原始数组，不返回 Model

## Application 层规范

- 命名: `App{Context}{Action}Service` (如 `AppOrderCommandService`)
- 职责: 编排领域服务调用顺序，不包含业务判断逻辑
- 事务: 跨聚合操作在 Application 层管理事务

## Interface 层规范

- Controller 只做: 参数校验 → 调用 AppService → 格式化响应
- Request: 严格验证所有入参，字段名使用 `snake_case`
- Transformer: 输出字段使用 `camelCase`，仅用于 API 响应格式化
- DTO: `toDto()` 直接映射 `$this->validated()`，不做字段名转换

## 字段命名规范

- 后端 API 字段统一使用 `snake_case`
- Request 入参必须是 `snake_case`
- Transformer 输出使用 `camelCase` (前端展示用)
- 前端必须适配后端字段名，后端不做兼容映射
