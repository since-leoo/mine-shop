# MineShop 商城系统

基于 Hyperf + DDD 架构的全栈电商系统，支持后台管理 + 微信小程序。

## 技术栈

- **后端框架**: Hyperf 3.x (Swoole 协程驱动)
- **架构模式**: DDD 领域驱动设计 (四层架构)
- **数据库**: MySQL 8.x
- **缓存**: Redis
- **支付**: 微信支付 / 余额支付
- **认证**: JWT (双 Token 机制: Access + Refresh)
- **权限**: RBAC 角色权限模型

## 项目结构

```
app/
├── Interface/          # 接口层 — 控制器、Request 验证、DTO、Transformer
│   ├── Admin/          # 后台管理端
│   └── Api/            # 小程序端 (API V1)
├── Application/        # 应用层 — 编排领域服务，不含业务逻辑
│   ├── Admin/          # 后台应用服务
│   │   ├── Catalog/    # 商品目录 (商品/分类/品牌)
│   │   ├── Marketing/  # 营销 (优惠券/秒杀/拼团)
│   │   ├── Trade/      # 交易 (订单/物流)
│   │   ├── Member/     # 会员
│   │   ├── Permission/ # 权限 (用户/角色/菜单)
│   │   ├── Organization/ # 组织 (部门/岗位/负责人)
│   │   └── Infrastructure/ # 基础设施 (附件/日志/系统配置)
│   └── Api/            # 小程序应用服务
│       ├── Cart/       # 购物车
│       ├── Coupon/     # 优惠券
│       ├── Home/       # 首页
│       ├── Member/     # 会员中心
│       ├── Order/      # 订单
│       ├── Payment/    # 支付
│       └── Product/    # 商品浏览
├── Domain/             # 领域层 — 核心业务逻辑
│   ├── Auth/           # 认证领域
│   ├── Catalog/        # 商品目录领域
│   │   ├── Brand/      # 品牌
│   │   ├── Category/   # 分类
│   │   └── Product/    # 商品 (SPU/SKU/快照)
│   ├── Marketing/      # 营销领域
│   │   ├── Coupon/     # 优惠券
│   │   ├── GroupBuy/   # 拼团
│   │   └── Seckill/    # 秒杀
│   ├── Trade/          # 交易领域
│   │   ├── Order/      # 订单 (实体/策略/库存锁)
│   │   ├── Payment/    # 支付
│   │   └── Shipping/   # 物流 (运费模板/运费计算)
│   ├── Member/         # 会员领域
│   ├── Permission/     # 权限领域 (RBAC)
│   ├── Organization/   # 组织领域
│   └── Infrastructure/ # 基础设施领域
│       ├── Attachment/  # 附件
│       ├── AuditLog/   # 审计日志
│       └── SystemSetting/ # 系统配置
└── Infrastructure/     # 基础设施层 — ORM Model、外部服务、工具
    ├── Model/          # Eloquent Model
    ├── Service/        # 外部服务适配 (支付/微信/地理)
    ├── Command/        # CLI 命令
    ├── Crontab/        # 定时任务
    └── Traits/         # 公共 Trait
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

### 订单下单流程

这是系统中最复杂的链路，涉及多个领域协作：

```
OrderController::submit()
  → AppApiOrderCommandService::submit()
    → DomainApiOrderCommandService::submit()
      ├── buildEntityFromInput()          # 构建订单实体
      │   ├── OrderMapper::getNewEntity() # 创建空实体
      │   ├── entity.initFromInput()      # 填充基础信息
      │   └── resolveAddress()            # 解析收货地址
      ├── guardPreorderAllowed()          # 预售检查
      ├── applySubmissionPolicy()         # 过期时间策略
      ├── OrderTypeStrategyFactory::make()# 策略工厂
      ├── strategy.validate()             # 商品校验
      ├── strategy.buildDraft()           # 构建草稿 (查快照/算价格)
      ├── applyFreight()                  # 运费计算
      ├── strategy.applyCoupon()          # 优惠券抵扣
      ├── strategy.adjustPrice()          # 价格调整
      ├── entity.verifyPrice()            # 前后端价格校验
      ├── stockService.acquireLocks()     # Redis 库存锁
      ├── stockService.reserve()          # 扣减库存
      ├── repository.save()              # 持久化订单
      ├── markCouponsUsed()              # 标记优惠券已使用
      └── strategy.postCreate()          # 后置钩子
```

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
| `validate()` | 校验订单合法性 |
| `buildDraft()` | 查询商品快照、计算价格 |
| `applyCoupon()` | 优惠券抵扣逻辑 |
| `adjustPrice()` | 最终价格调整 |
| `postCreate()` | 订单创建后置逻辑 |

当前已实现: `NormalOrderStrategy` (普通订单)，可扩展秒杀订单、拼团订单等。

## 文档导航

- [DDD 分层规范](./ddd-conventions.md) — 四层依赖规则、Entity/Mapper/Repository 设计原则
- [Admin API 速查表](./admin-api.md) — 后台管理端全部接口，含路径、方法、权限码
- [小程序 API 速查表](./mini-api.md) — 微信小程序端全部接口，含请求/响应示例

完整文档站点请运行 `cd shopdoc && npm run dev` 查看 VitePress 文档。
