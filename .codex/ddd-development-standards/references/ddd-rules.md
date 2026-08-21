# MineShop DDD 规则参考

本参考提炼自仓库 `docs/DDD-ARCHITECTURE.md`。遇到更新或歧义时，以仓库原文和相邻模块的既有约定为准。

## 实现骨架

### Interface

- 让 `Request` 定义场景化规则（如创建、更新），从 `validated()` 取值，并在 `toDto(?int $id, int $operatorId)` 中映射 DTO。
- 让 DTO 实现 Domain Contract；通过 getter 向 Domain 暴露数据。
- 让 Controller 注入应用服务和当前用户上下文，调用 `Request::toDto()` 后转交服务并返回标准响应。
- 让 Transformer 负责 API/小程序的展示字段；Domain/DomainApi Query 仅返回领域查询结果或 Model 数据。

### Application

- 在写方法的事务边界内调用 Domain Service。
- 在事务成功后发布必要的领域事件，并使受影响缓存失效。
- 不在此层放业务规则、Entity 转换、Repository 细节或前端响应拼装。

### Domain：复杂模型

创建：`Mapper::getNewEntity()` -> `Entity::create($input)` -> `Repository::create($entity->toArray())` -> 必要时同步关系。

更新：`Repository::findById()` -> `Mapper::fromModel()` -> `Entity::update($input)` -> `Repository::updateById()` -> 必要时同步关系。

`getEntity()` 仅用于需要执行实体行为（如状态改变、授权、重置）的 Domain Service 内部流程：查询 Model、未找到时抛 `RuntimeException`、转换成 Entity。不要由 Application 获取或操作 Entity。

Entity 的 setter/行为方法维护不变量并调用 `markDirty()`。`toArray()` 在更新时只输出 dirty 且非 null 的字段；新实体未标记 dirty 时可以输出可持久化的非 null 字段。Mapper 还原现有实体时需避免污染 dirty 状态。

### Domain：简单 CRUD

让 DTO 的 `toArray()` 根据创建/更新语义返回持久化字段，且在对应 Contract 声明该方法。Domain Service 直接调用 Repository 并处理简单的关联 `sync()`；不要为无行为的 CRUD 创建空洞 Entity。

## 验证边界

| 位置 | 放置内容 |
| --- | --- |
| Request | required、类型、格式、长度、范围、枚举、exists/unique、正则及基础数值比较 |
| ValueObject | 跨字段关系、复杂领域规则、领域概念完整性、计算方法 |
| Entity | 状态迁移、不变量、依赖查询的领域判断、复杂业务行为 |

不要在 ValueObject 或 Entity 重复格式、类型或“正数”等基础校验；这些应尽量在 Request 中完成。

## 字段规则

- 以对应 migration 的字段作为 DTO 和 Entity 持久化字段的唯一来源。
- DTO 属性使用数据库的 `snake_case` 名称，数据类型和可空性与表对应。
- Entity 属性可使用 `camelCase`，但 `toArray()` 返回表字段的 `snake_case`。
- 不要将 `operator_id` 等非表字段定义成 DTO 的持久化字段；由用例/审计机制在恰当位置处理。
- ValueObject 可提供折扣、剩余时间等计算字段，但不要持久化它们。

## 异常和值对象

- 违反领域规则时抛 `\DomainException`。
- 未找到领域资源时抛 `\RuntimeException`。
- 参数不合法时抛 `\InvalidArgumentException`。
- 不创建诸如 `UserNotFoundException` 的自定义领域异常。
- 实体行为的返回对象以 `Vo` 结尾，例如 `GrantRolesVo`；其中包含 `success`、后续同步所需数据等语义化结果。
