# 项目介绍

Mine Shop 是一套面向企业的电商与会员运营解决方案，基于 **Hyperf 3 + PHP 8.1 + Vue 3 + Element Plus** 打造，采用 DDD（领域驱动设计）与 CQRS 等实践，实现商品、订单、营销、会员、地址库与数据分析等能力。

## 为什么选择 Mine Shop？

### 🏗️ 企业级架构
- DDD 四层：Interface / Application / Domain / Infrastructure。
- CQRS：读写分离，降低复杂度。
- 事件驱动：订单、钱包、库存、日志通过事件解耦。
- 可观测：统一日志、链路 ID、操作审计。

### ⚡ 高性能协程
- Hyperf 3 + Swoole 5 协程化运行。
- Redis + Lua 原子库存，支持秒杀/团购峰值。
- Cron + CLI 任务（如 `mall:sync-regions`）在协程环境下高效执行。

### 🧾 完整业务闭环
- 商品/SKU/品牌/分类/附件管理。
- 订单、支付、履约、售后、日志。
- 秒杀、团购、优惠券、会员标签、积分、钱包。
- Geo 四级地址库、地区联动、会员地区画像。
- 会员概览驾驶舱、等级结构、运营待办。

### 🔐 安全合规
- JWT + RBAC + 数据权限。
- 操作与登录日志、权限审计。
- Lua 锁 + 幂等校验避免重复扣减。
- 支付对账、退款、钱包双写保障。

## 技术栈

| 层级 | 技术 | 说明 |
| ---- | ---- | ---- |
| 后端 | PHP 8.1+, Hyperf 3, Swoole 5 | 协程框架、AOP、调度、依赖注入 |
| 数据 | MySQL 8, Redis 6, Lua | 关系数据、缓存、库存脚本 |
| 前端 | Vue 3, TypeScript, Vite, Element Plus, MineAdmin UI | 组件化后台、ECharts 数据可视化 |
| 文档 | VitePress | 当前文档站点 |

## 系统要求

| 组件 | 最低版本 |
| ---- | -------- |
| PHP | 8.1 |
| Swoole | 5.0 |
| MySQL | 8.0 |
| Redis | 6.0 |
| Node.js | 20（用于前端与文档） |
| Composer | 2.0 |

需启用的 PHP 扩展：`swoole`、`redis`、`pdo_mysql`、`mbstring`、`json`、`openssl`、`bcmath`、`pcntl`、`posix`。

## 项目结构

```
.
├── app/
│   ├── Interface/      # 控制器、请求、Middleware、VO
│   ├── Application/    # Command/Query Service、Mapper、事件处理
│   ├── Domain/         # 实体、值对象、服务、策略、仓储接口
│   └── Infrastructure/ # ORM 模型、Repository 实现、缓存、命令、Geo、支付等适配层
├── web/                # Vue 3 + Element Plus 后台前端
├── shopDoc/            # 官方文档（即本目录）
├── config/             # 配置文件（包括 pay、mall、crontab 等）
├── databases/          # 迁移与种子
└── bin/hyperf.php      # CLI 入口（migrate、task、mall:sync-regions 等）
```

## 关键能力

| 模块 | 摘要 |
| ---- | ---- |
| 商品中心 | 商品/SPU/SKU、品牌、分类、属性、相册、批量导入 |
| 订单中心 | 下单、支付、发货、取消、库存回滚、日志、策略化订单类型 |
| 营销中心 | 秒杀、团购、优惠券（满减/折扣/权益包）、活动表单 |
| 会员中心 | 会员档案、等级、标签、钱包、积分、概览驾驶舱、地区画像 |
| Geo 地址库 | 自建 `geo_regions` 表、同步命令、PCAS 接口、关键字搜索、前端 useGeo Hook |
| 系统管理 | 菜单、角色、权限、数据权限、操作/登录日志、设置中心 |

## 开发流程概览

1. 克隆项目并安装依赖（见 [安装部署](/guide/installation)）。
2. 配置 `.env`、数据库、Redis、支付、Geo 等。
3. 执行迁移及初始数据：`php bin/hyperf.php migrate && php bin/hyperf.php db:seed`。
4. 可选：`php bin/hyperf.php mall:sync-regions` 同步四级地址。
5. 启动 Hyperf 服务：`php bin/hyperf.php start` 或 `server:watch`。
6. 启动前端：`cd web && npm install && npm run dev`。
7. 登录后台（默认账号在 `databases/seeders`）体验全部功能。

## 下一步

- [安装部署](/guide/installation) – 后端、前端、Docker、生产部署指引。

- [配置说明](/guide/configuration) – `.env`、支付、商城、Geo、Crontab 等配置。

- [DDD 架构](/architecture/ddd) – 深入理解分层模型与数据流。

若你负责市场/方案输出，可直接参考 [index.md](/) 的亮点与 Feature 说明。*** End Patch
