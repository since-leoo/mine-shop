---
name: MineShop-ddd-development-standards
description: MineShop PHP/Hyperf 项目的 DDD 后端开发与代码评审规范。用于在 MineShop 仓库新增或修改 Controller、Request、DTO、Contract、Application、Domain Service、Entity、Mapper、Repository、ValueObject、Transformer 或相关测试时，强制遵守 docs/DDD-ARCHITECTURE.md 定义的分层职责、命名、数据流、验证和持久化规则。
---

# MineShop DDD 开发规范

按本 skill 实现或评审 MineShop 的 PHP 后端变更。先阅读仓库内 `docs/DDD-ARCHITECTURE.md`；本 skill 与文档有冲突时，以文档为准。

## 工作流程

1. 识别入口和调用端：后台管理端使用 `App*Service`，小程序/API 使用 `AppApi*Service` 与需要时的 `DomainApi*Service`。
2. 判断用例是命令还是查询，并按现有模块目录、命名和相邻实现落位。
3. 判断领域复杂度：存在行为、不变量、状态流转、聚合、复杂规则或 dirty 更新时使用实体；纯 CRUD 使用 DTO 的 `toArray()`。
4. 沿层级实现并保持依赖方向：`Controller -> Application -> Domain -> Repository`。不得跳过 Application 或把领域逻辑放进 Interface/Application。
5. 实现后逐项执行“交付前检查”。涉及具体规则时读取 `references/ddd-rules.md`。

## 分层与命名

| 层级 | 放置和职责 | 服务前缀 |
| --- | --- | --- |
| Interface | Controller 接收请求；Request 验证并 `toDto()`；DTO 实现 Domain Contract；Transformer 组装接口展示数据 | 无 |
| Application | 编排用例、事务、事件、缓存；按读写拆分 | 后台 `App`；API `AppApi` |
| Domain | 领域规则、实体、值对象、Mapper、Repository 协作 | `Domain`；API 专属 `DomainApi` |

- 后台命令/查询服务分别位于 `Application/Commad/`、`Application/Query/`，命名为 `App{Aggregate}{Command|Query}Service`。
- 小程序/API 应用服务位于 `Application/Api/`，命名为 `AppApi{Aggregate}{Command|Query}Service`；API 专属领域服务位于 `Domain/*/Api/{Command|Query}/`，命名为 `DomainApi{Aggregate}{Command|Query}Service`。
- 后台标准链路：`Controller -> App*Service -> Domain*Service -> Repository`；API 标准链路：`Controller -> AppApi*Service -> DomainApi*Service -> Repository`。
- 让 Controller 只做协议处理；让 Application 只做编排；让 Domain 承载业务决策。禁止 Controller 直接访问 Repository，禁止 Application 直接创建/操作 Entity 或拼装接口展示结构。

## 实体决策

使用 Entity，如果存在任一情况：复杂业务规则、多个领域行为、状态机/生命周期、聚合根、dirty 字段跟踪。让 Domain Service 使用 `Mapper::getNewEntity()` 或 `Mapper::fromModel()`，调用 Entity 的 `create()`、`update()` 或语义化行为方法，再持久化 `Entity::toArray()`。

不使用 Entity，如果仅是无业务规则和状态变更的简单 CRUD/关联同步。让 DTO 实现对应 Contract 并声明 `toArray()`，由 Domain Service 直接将其传给 Repository。

对需要实体的 Domain Service，提供 `getEntity(int $id): Entity`：通过 Repository 取 Model，未找到时抛出 `\RuntimeException`，再由 Mapper 转为 Entity。只在 Domain 内调用它。

## 不可违反的规则

- 在 Request 做必填、类型、格式、范围、枚举、唯一性和基础数值校验；在 ValueObject/Entity 做跨字段规则、状态转换、不变量和复杂行为校验。
- 让 Entity 的行为方法接收 Contract/DTO 并在内部组装状态；不要由 Application 或 Domain Service 在外部拼装实体字段。
- 让 Entity 使用 dirty 标记；其 `toArray()` 只返回修改字段。Mapper 从 Model 恢复实体时必须不会把“加载数据”误判为“用户修改”。
- DTO 的持久化字段与迁移表字段一一对应，使用 `snake_case` 且类型匹配；不要把操作人等非表字段混入 DTO。Entity 可用 `camelCase`，但 `toArray()` 必须输出表字段的 `snake_case` 键。计算数据只留在 ValueObject。
- 把事务、领域事件和缓存失效放在 Application；不要把业务规则或接口展示字段放在这里。由 Controller + Transformer 负责 API/小程序响应结构。
- 使用 PHP SPL 标准异常：领域规则用 `\DomainException`，资源不存在用 `\RuntimeException`，非法参数用 `\InvalidArgumentException`；不要新增自定义领域异常。
- 让实体行为的结果值对象以 `Vo` 结尾，不要以 `Result` 结尾。

## 交付前检查

- 确认服务名称、目录和链路与入口类型（后台或 API）一致。
- 确认 Request 完成输入验证和 `toDto()`，DTO 已实现 Domain Contract。
- 确认复杂用例经由 Mapper/Entity，简单 CRUD 经由 `Contract::toArray()`，没有混用。
- 确认写操作事务、缓存失效与必要事件均在 Application，且无领域业务逻辑泄漏。
- 对照迁移检查 DTO、Entity 的字段名、类型、可空性、默认值及 `toArray()` 键。
- 确认领域规则、异常类型、关联同步和 API Transformer 的职责归属正确，并运行最贴近改动的测试或静态检查。

## 参考

需要实现模板、验证边界、字段对齐、`getEntity()` 或异常/值对象细节时，读取 `references/ddd-rules.md`。该参考由仓库的 `docs/DDD-ARCHITECTURE.md` 提炼；实现前仍须检查目标模块已有的相邻代码。
