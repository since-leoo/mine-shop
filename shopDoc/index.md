---
layout: home

hero:
  name: "Mine Shop"
  text: "企业级电商与会员运营基座"
  tagline: "基于 Hyperf 3 + Vue 3 + DDD 的全链路解决方案，覆盖商品、订单、营销、会员、地址库与数据洞察"
  actions:
    - theme: brand
      text: 立即开始
      link: /guide/installation
    - theme: alt
      text: 架构蓝图
      link: /architecture/ddd
    - theme: alt
      text: API 参考
      link: /api/

features:
  - icon: 🧭
    title: DDD 全域建模
    details: Interface / Application / Domain / Infrastructure 四层解耦，CQRS 与事件驱动并行，核心逻辑高度内聚。
  - icon: 🚀
    title: 协程级性能
    details: Hyperf 3 + Swoole 5 + Redis/Lua 组合拳，库存、秒杀、团购等高并发场景安全可控。
  - icon: 🧾
    title: 完整业务闭环
    details: 商品、订单、优惠券、团购、秒杀、会员钱包、积分、标签、渠道与概览大屏一应俱全。
  - icon: 🗺️
    title: 四级地址库
    details: 内置 geo_regions 库及 /geo/pcas、/geo/search 接口，支持省市区街四级联动与定时同步。
  - icon: 📊
    title: 数据中枢
    details: 多维趋势、等级、地区分布与渠道洞察，搭配操作待办与画像卡片，运营、技术一屏掌控。
  - icon: 🔐
    title: 全面安全
    details: JWT + RBAC + 数据权限 + 操作日志 + 风险兜底脚本，满足政企与品牌方上线标准。
---

## 2026 关键更新

- ✅ 新增 **Geo Region 基础设施**：`mall:sync-regions` 命令、专属服务、CRON 轮询与缓存，填补四级地址库空白。
- ✅ 会员模块升级：支持 `region_path`、省市区街抽象，以及「会员概览 → 地区构成」等运营视图。
- ✅ 团购、秒杀、优惠券全流程表单体验优化，新增默认值、校验规则与动态开关。
- ✅ 前端统一切换为 **Vue 3 + Element Plus + MineAdmin 生态组件**，新增 useGeo Hook、Cascader、ECharts 主题。
- ✅ 新增 `/geo/pcas`、`/geo/search`、`/admin/member/member/overview` 等接口，服务端自带缓存、协程批处理。

## 技术栈

| 层级 | 技术 | 说明 |
| ---- | ---- | ---- |
| 后端 | PHP 8.1+ / Hyperf 3 / Swoole 5 | 协程化微服务框架、事件系统、AOP、Crontab |
| 数据 | MySQL 8、Redis 6、Lua Script | 主存储、缓存、库存脚本、分布式锁 |
| 前端 | Vue 3、TypeScript、Vite、Element Plus、ECharts | MineAdmin UI 套件、Composition API、动态大屏 |
| 文档 | VitePress、Markdown | 支持多语言、版本化、自动部署 |

## 模块矩阵

- **商品中心**：品牌、分类、规格、属性、SKU、搜索及上架流程。
- **订单履约**：多订单类型策略、拆单、日志、发货、售后、库存回滚。
- **营销玩法**：优惠券（满减/折扣/发放）、团购（原价/团购价/库存/时间默认值）、秒杀（活动/场次/商品）、会员权益。
- **会员运营**：等级、标签、钱包、积分、概览驾驶舱、标签圈选、地址管理、四级地区字段。
- **系统基座**：权限、菜单、数据权限、审计、Settings、任务调度、附件中心。
- **Geo 地址库**：迁移、领域模型、同步服务、命令与定时任务、PCAS + 关键字搜索接口、前端级联选择与缓存。

## 快速上手

```bash
# 1. 克隆并安装依赖
git clone https://github.com/mineadmin/mine-shop.git
cd mine-shop
composer install

# 2. 初始化环境
cp .env.example .env
php bin/hyperf.php migrate
php bin/hyperf.php db:seed

# 3. 安装系统消息和微信插件
php bin/hyperf.php plugin:install since/wechat
php bin/hyperf.php plugin:install since/system-message

# 4. 同步地区库
php bin/hyperf.php mall:sync-regions

# 4. 启动服务
php bin/hyperf.php start

# 5. 启动前端
cd web && npm install && npm run dev
```

## 核心目录

```
app/
├── Interface/        # Admin & Api 控制器、请求、VO
├── Application/      # Command & Query Service、Mapper（CQRS）
├── Domain/           # 实体、值对象、服务、策略、仓储抽象
└── Infrastructure/   # ORM 模型、存储、队列、脚本、命令、Geo 服务
web/                  # Vue 3 + Element Plus 后台前端
shopDoc/              # 官方文档（当前站点）
```

## 下一步

- [指南 · 认识系统](/guide/) – 业务、技术背景与要求
- [安装部署](/guide/installation) – 后端 / 前端 / Docker / 生产方案
- [DDD 架构](/architecture/ddd) – 分层、CQRS、事件驱动实现细节
- [API 参考](/api/) – 后台、前台、Geo、认证接口全集
