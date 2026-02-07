# 分层设计

Mine Shop 严格遵循「接口 → 应用 → 领域 → 基础设施」四层架构，依赖方向单向向下。

```
Interface (HTTP/CLI) ─► Application (CQRS/编排) ─► Domain (模型/服务/事件) ─► Infrastructure (ORM/缓存/Lua)
```

## Interface Layer — 接口层

**职责**：接收请求、验证参数、注入认证/权限、格式化响应。

**目录**：`app/Interface/Admin|Api|Common`

- `Controller`：HTTP 路由注解，调用 Application Service
- `Request`：参数验证（snake_case 字段，严格类型 + 长度 + 范围校验）
- `Middleware`：JWT 认证、RBAC 权限、数据权限
- `Transformer`：Entity → 前端响应（camelCase）
- `Result` / `ResultCode`：统一响应格式与错误码

```php
#[GetMapping(path: 'overview')]
public function overview(MemberRequest $request): Result
{
    $filters = $request->validated();
    return $this->success($this->queryService->overview($filters));
}
```

## Application Layer — 应用层

**职责**：CQRS 读写分离、事务控制、DTO/Entity 互转、事件编排。

**目录**：按场景分组

```
app/Application/
├── Admin/                          # 后台管理
│   ├── Catalog/                    # 商品、品牌、分类
│   ├── Trade/                      # 订单、发货
│   ├── Marketing/                  # 优惠券、秒杀、团购
│   ├── Member/                     # 会员管理
│   ├── Permission/                 # 权限管理
│   ├── Organization/               # 组织管理
│   └── Infrastructure/             # 附件、设置、日志、Listener
└── Api/                            # 小程序 / 前端
    ├── Cart/                       # 购物车
    ├── Coupon/                     # 优惠券查询
    ├── Home/                       # 首页聚合
    ├── Member/                     # 会员中心、地址、认证
    ├── Order/                      # 下单流程
    ├── Payment/                    # 支付编排
    └── Product/                    # 商品查询
```

**关键组件**：

- `CommandService`：写操作 + 事务提交
- `QueryService`：查询、统计、缓存、分页
- `Mapper`：DTO ⇄ Entity 转换
- `Listener`：订阅领域事件（如 `UserOperationSubscriber` 在 `Application/Admin/Infrastructure/Listener/`）

## Domain Layer — 领域层

**职责**：封装业务规则，是唯一可以修改业务状态的地方。

**目录**：`app/Domain/`，按限界上下文分组

```
app/Domain/
├── Auth/                           # 认证
├── Catalog/{Brand,Category,Product}# 商品目录
├── Trade/{Order,Payment,Shipping}  # 交易
├── Marketing/{Coupon,GroupBuy,Seckill} # 营销
├── Member/                         # 会员
├── Permission/                     # 权限
├── Organization/                   # 组织
└── Infrastructure/{Attachment,AuditLog,SystemSetting} # 基础设施领域
```

**组成**：

- **Entity**：聚合根（`OrderEntity`、`MemberEntity`）
- **Value Object**：不可变对象（`OrderPriceValue` — 所有金额 int 分）
- **Domain Service**：跨实体业务（`DomainOrderStockService`、`DomainPayService`）
- **Repository Interface**：持久化契约
- **Strategy / Factory**：`OrderTypeStrategyInterface`、`OrderTypeStrategyFactory`
- **Contract**：输入契约（`OrderPreviewInput`、`OrderSubmitInput`）
- **Domain Event**：`OrderCreatedEvent`、`MemberBalanceAdjusted`

## Infrastructure Layer — 基础设施层

**职责**：为领域层提供技术能力。领域层只依赖接口，具体实现下沉到该层。

**目录**：`app/Infrastructure/`

- **Model**：Hyperf ORM 模型
- **Repository Impl**：实现领域仓储接口
- **Cache / Redis / Lua**：库存脚本、分布式锁
- **Command / Crontab**：`mall:sync-regions`、`system:check-tables`
- **Service Adapter**：支付（yansongda/pay）、Geo 同步、附件存储
- **Traits**：`PaymentTrait`、`BootTrait`、`RepositoryOrderByTrait`

## 横切关注点

| 能力 | 实现位置 | 说明 |
| ---- | -------- | ---- |
| JWT 认证 | Interface Middleware | 解码 Token、注入 User Context |
| RBAC + 数据权限 | Interface + Infrastructure (AOP) | `PermissionMiddleware` + `DataScope` 注解 |
| 事务 | Application Layer | CommandService 以 `db->transaction()` 包裹 |
| 日志 & 审计 | Application Listener | `UserOperationSubscriber` 记录操作日志 |
| 缓存 | Application / Infrastructure | QueryService 使用 CacheInterface |
| Lua / 分布式锁 | Infrastructure | 库存扣减脚本 |

## 开发建议

1. **接口层避免直接操作模型**，改由应用层服务注入
2. **新增业务**先定义领域实体与仓储接口，再补基础设施实现
3. **查询性能**：QueryService 可选择缓存策略，失效策略放在应用层
4. **事件监听器**：领域事件在 Domain 层触发，Listener 放在 `Application/` 或 `Infrastructure/` 层
5. **脚本/任务**：命令行位于 `app/Infrastructure/Command`，Crontab 配置在 `config/autoload/crontab.php`

掌握分层职责后，可前往 [DDD 架构](/architecture/ddd) 与 [设计模式](/architecture/patterns) 阅读更深入的原理。
