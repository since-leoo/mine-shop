---
layout: home

hero:
  name: "商城系统"
  text: "企业级电商解决方案"
  tagline: 基于 Hyperf + DDD 架构，支持秒杀、团购等多种业务模式
  actions:
    - theme: brand
      text: 快速开始
      link: /guide/installation
    - theme: alt
      text: 架构设计
      link: /architecture/ddd
    - theme: alt
      text: API 文档
      link: /api/

features:
  - icon: 🏗️
    title: DDD 架构
    details: 采用领域驱动设计，清晰的四层架构，职责明确，易于维护和扩展
  - icon: ⚡
    title: 高性能
    details: 基于 Hyperf + Swoole，支持协程，使用 Redis + Lua 实现高性能库存管理
  - icon: 🛒
    title: 完整功能
    details: 支持普通订单、秒杀、团购等多种业务模式，满足各类电商场景
  - icon: 💰
    title: 多支付方式
    details: 支持支付宝、微信支付、余额支付等多种支付方式
  - icon: 📦
    title: 库存管理
    details: 使用 Lua 脚本实现原子性库存扣减，分布式锁保证并发安全
  - icon: 🔐
    title: 权限管理
    details: 完善的 RBAC 权限系统，支持菜单权限、数据权限等
  - icon: 📊
    title: 数据统计
    details: 丰富的数据统计功能，支持订单、产品、会员等多维度分析
  - icon: 🔌
    title: 易于扩展
    details: 策略模式、工厂模式等设计模式，支持快速扩展新业务
---

## 技术栈

- **后端框架**: Hyperf 3.x
- **运行环境**: PHP 8.1+ / Swoole 5.x
- **数据库**: MySQL 8.0+
- **缓存**: Redis 6.0+
- **前端框架**: Vue 3 + Arco Design
- **架构模式**: DDD (领域驱动设计)

## 核心特性

### 🎯 领域驱动设计

采用 DDD 架构，将业务逻辑清晰地划分为四层：

- **Interface Layer**: 接口层，处理 HTTP 请求和响应
- **Application Layer**: 应用层，协调领域服务，实现 CQRS 模式
- **Domain Layer**: 领域层，封装核心业务逻辑
- **Infrastructure Layer**: 基础设施层，提供技术支持

### 🚀 高性能库存管理

- 使用 Redis + Lua 脚本实现原子性库存扣减
- 分布式锁防止并发冲突
- 支持自动重试和回滚机制
- 高并发场景下性能优异

### 🛍️ 丰富的业务模式

- **普通订单**: 标准的电商购物流程
- **秒杀活动**: 支持多场次秒杀，限购控制
- **团购活动**: 灵活的成团规则，自动成团/退款
- **优惠券**: 满减、折扣等多种优惠方式

### 💳 完善的支付系统

- 支付宝支付（App、Web、H5）
- 微信支付（小程序、公众号、App）
- 余额支付
- 支付回调处理
- 退款管理

## 快速开始

```bash
# 克隆项目
git clone <repository-url>

# 安装依赖
composer install

# 配置环境变量
cp .env.example .env

# 运行数据库迁移
php bin/hyperf.php migrate
php bin/hyperf.php db:seed

# 启动服务
php bin/hyperf.php start
```

详细安装步骤请查看 [安装部署](/guide/installation) 文档。

## 项目结构

```
app/
├── Application/      # 应用层 (CQRS)
├── Domain/          # 领域层 (核心业务)
├── Infrastructure/  # 基础设施层
└── Interface/       # 接口层 (HTTP)
```

## 贡献指南

欢迎提交 Issue 和 Pull Request！

## 许可证

MIT License
