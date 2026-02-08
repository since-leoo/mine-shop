# MineShop 商城系统

基于 Hyperf + DDD 架构的全栈电商系统，支持后台管理 + 微信小程序。采用自研插件管理器 `since-leoo/hyperf-plugin` 实现业务模块热插拔。

## 技术栈

- **后端框架**: Hyperf 3.1 (Swoole 5.0+ 协程驱动)
- **架构模式**: DDD 领域驱动设计 (四层架构)
- **插件系统**: since-leoo/hyperf-plugin (自研插件管理器)
- **数据库**: MySQL 8.x
- **缓存**: Redis
- **支付**: 微信支付 / 余额支付
- **认证**: JWT (双 Token 机制: Access + Refresh)
- **权限**: RBAC 角色权限模型
- **运行环境**: PHP 8.2+ / Swoole 5.0+

## 项目结构

```
app/                        # 主应用（核心业务）
├── Interface/              # 接口层 — 控制器、Request 验证、DTO
│   ├── Admin/              # 后台管理端
│   └── Api/                # 小程序端 (API V1)
├── Application/            # 应用层 — 编排领域服务，不含业务逻辑
│   ├── Admin/              # 后台应用服务
│   └── Api/                # 小程序应用服务
├── Domain/                 # 领域层 — 核心业务逻辑
│   ├── Auth/               # 认证领域
│   ├── Catalog/            # 商品目录 (品牌/分类/商品)
│   ├── Trade/              # 交易领域
│   │   ├── Order/          # 订单 (实体/策略/异步Job)
│   │   └── Payment/        # 支付
│   ├── Member/             # 会员领域
│   ├── Permission/         # 权限领域 (RBAC)
│   ├── Organization/       # 组织领域
│   └── Infrastructure/     # 基础设施领域 (附件/日志/配置)
└── Infrastructure/         # 基础设施层 — ORM Model、外部服务

plugins/                    # 插件目录（可插拔业务模块）
├── seckill/                # 秒杀活动
├── group-buy/              # 拼团活动
├── coupon/                 # 优惠券
├── shipping/               # 运费计算
├── geo/                    # 地理服务
├── system-message/         # 系统消息
└── wechat/                 # 微信集成
```

## 架构设计

### 四层调用规则

```
Interface → Application → Domain → Infrastructure
```

- **Interface**: 只做参数校验和响应格式化，不含业务逻辑
- **Application**: 编排多个领域服务，处理事务边界
- **Domain**: 核心业务规则，Entity 承载状态变更，Service 编排领域操作
- **Infrastructure**: ORM、缓存、外部 API 等技术实现

### 插件架构

营销活动（秒杀、拼团）、优惠券、运费计算等模块已从主应用拆分为独立插件。核心应用只定义接口契约（如 `CouponServiceInterface`、`FreightServiceInterface`），插件通过 ConfigProvider 注册适配器实现。详见 [插件系统](./architecture/plugin-system.md)。

### 订单下单流程（异步提交）

系统中最复杂的链路，采用同步校验 + 异步入库的架构：

```
OrderController::submit()
  → AppApiOrderCommandService::submit()
    → DomainApiOrderCommandService::submit()
      │
      │ ── 同步阶段 ──
      ├── buildOrder()
      │   ├── buildEntityFromInput()          # 构建实体 + 解析地址
      │   ├── strategy.validate()             # 策略校验（会员、商品）
      │   ├── strategy.buildDraft()           # 构建草稿（查快照、算价格）
      │   ├── strategy.applyFreight()         # 运费计算（策略自行处理）
      │   └── strategy.applyCoupon()          # 优惠券抵扣（一次一张）
      ├── entity.verifyPrice()                # 前后端价格校验
      ├── stockService.reserve()              # Lua 原子扣减库存（无锁）
      ├── pendingCache.markProcessing()       # Redis 标记下单中
      └── driverFactory.push(OrderCreateJob)  # 投递异步 Job
      │
      │ ── 异步阶段（OrderCreateJob）──
      ├── rebuildEntity()                     # 从快照重建 Entity
      ├── strategy.rehydrate()                # 策略恢复活动实体
      └── DB::transaction()
          ├── repository.save()               # 持久化订单
          └── strategy.postCreate()           # 后置处理（含优惠券核销）
```

前端通过 `tradeNo` 轮询下单结果。若异步 Job 最终失败，自动回滚 Redis 库存。

### 支付流程

```
OrderController::payment()
  → AppApiOrderPaymentService::payment()
    → DomainPayService::init(order, member)
      ├── payByWechat()                  # 微信支付
      │   ├── paymentService.create()    # 创建支付记录
      │   └── ysdPayService.pay()        # 调用支付网关
      └── payByBalance()                 # 余额支付
          ├── paymentService.create()    # 创建支付记录
          ├── walletService.getEntity()  # 加载钱包
          ├── walletEntity.changeBalance()# 扣款
          ├── orderEntity.markPaid()     # 标记已支付
          ├── orderService.update()      # 更新订单
          └── event(MemberBalanceAdjusted)# 发送事件
```

### 策略模式

订单类型通过策略模式扩展，每种订单类型实现 `OrderTypeStrategyInterface`：

| 方法 | 职责 |
|------|------|
| `validate()` | 校验订单合法性（会员、商品） |
| `buildDraft()` | 查询商品快照、计算商品金额 |
| `applyFreight()` | 计算并设置运费（各策略自行处理） |
| `applyCoupon()` | 优惠券抵扣（一次只能用一张） |
| `rehydrate()` | 从快照恢复活动实体（异步 Job 中调用） |
| `postCreate()` | 订单创建后置逻辑（含优惠券核销） |

当前已实现：
- `NormalOrderStrategy` — 普通订单（主应用内置）
- `SeckillOrderStrategy` — 秒杀订单（seckill 插件提供）
- `GroupBuyOrderStrategy` — 拼团订单（group-buy 插件提供）

## 文档导航

- [DDD 分层规范](./ddd-conventions.md) — 四层依赖规则、Entity/Mapper/Repository 设计原则
- [插件系统](./architecture/plugin-system.md) — 插件管理器、plugin.json 规范、接口解耦模式
- [设计模式](./architecture/patterns.md) — 策略/工厂/CQRS/事件等模式应用
- [订单设计](./core/order-design.md) — 订单领域模型、异步提交流程、状态机
- [Admin API 速查表](./admin-api.md) — 后台管理端全部接口，含路径、方法、权限码
- [小程序 API 速查表](./mini-api.md) — 微信小程序端全部接口，含请求/响应示例

完整文档站点请运行 `cd shopDoc && npm run dev` 查看 VitePress 文档。
