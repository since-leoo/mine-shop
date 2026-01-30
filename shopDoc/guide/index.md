# 介绍

## 项目概述

这是一个基于 **Hyperf 框架** 和 **Swoole** 的企业级电商商城系统，采用 **DDD（领域驱动设计）** 架构，支持普通订单、秒杀、团购等多种业务模式。

## 为什么选择本系统？

### 🏗️ 企业级架构

采用 DDD 架构设计，代码结构清晰，易于维护和扩展：

- **清晰的分层**: Interface、Application、Domain、Infrastructure 四层分离
- **CQRS 模式**: 读写分离，性能优化
- **事件驱动**: 解耦业务逻辑，支持异步处理
- **策略模式**: 灵活支持多种订单类型

### ⚡ 高性能

- **Swoole 协程**: 高并发处理能力
- **Redis 缓存**: 模型缓存、库存缓存
- **Lua 脚本**: 原子性操作，保证数据一致性
- **连接池**: 数据库、Redis 连接池优化

### 🛒 完整的电商功能

- **产品管理**: 多规格 SKU、属性管理、图片管理
- **订单系统**: 订单流程、发货管理、订单日志
- **秒杀功能**: 多场次秒杀、限购控制
- **团购功能**: 灵活成团规则、自动成团
- **会员系统**: 会员等级、积分、余额
- **优惠券**: 满减、折扣等多种优惠
- **支付系统**: 支付宝、微信、余额支付

### 🔐 安全可靠

- **JWT 认证**: 无状态认证，支持刷新令牌
- **RBAC 权限**: 菜单权限、数据权限
- **操作日志**: 完整的操作审计
- **数据权限**: 部门数据隔离
- **分布式锁**: 防止并发冲突

## 技术栈

### 后端

| 技术 | 版本 | 说明 |
|------|------|------|
| PHP | 8.1+ | 编程语言 |
| Hyperf | 3.x | 协程框架 |
| Swoole | 5.x | 高性能网络通信引擎 |
| MySQL | 8.0+ | 关系型数据库 |
| Redis | 6.0+ | 缓存和队列 |

### 前端

| 技术 | 版本 | 说明 |
|------|------|------|
| Vue | 3.x | 前端框架 |
| Arco Design | 2.x | UI 组件库 |
| Vite | 5.x | 构建工具 |
| TypeScript | 5.x | 类型系统 |

## 系统要求

### 服务器要求

- **操作系统**: Linux / macOS / Windows
- **PHP**: >= 8.1
- **Swoole**: >= 5.0
- **MySQL**: >= 8.0
- **Redis**: >= 6.0
- **Composer**: >= 2.0

### PHP 扩展要求

```bash
php --ri swoole    # Swoole 扩展
php --ri redis     # Redis 扩展
php --ri pdo_mysql # MySQL PDO 扩展
php --ri json      # JSON 扩展
php --ri openssl   # OpenSSL 扩展
php --ri pcntl     # PCNTL 扩展
php --ri posix     # POSIX 扩展
```

## 项目结构

```
.
├── app/                      # 应用代码
│   ├── Application/         # 应用层 (CQRS)
│   │   ├── Order/          # 订单应用服务
│   │   ├── Product/        # 产品应用服务
│   │   ├── Seckill/        # 秒杀应用服务
│   │   └── GroupBuy/       # 团购应用服务
│   ├── Domain/             # 领域层 (核心业务)
│   │   ├── Order/          # 订单领域
│   │   ├── Product/        # 产品领域
│   │   ├── Seckill/        # 秒杀领域
│   │   └── GroupBuy/       # 团购领域
│   ├── Infrastructure/     # 基础设施层
│   │   ├── Model/          # Eloquent 模型
│   │   ├── Library/        # 工具库
│   │   └── Exception/      # 异常处理
│   └── Interface/          # 接口层
│       ├── Admin/          # 后台接口
│       ├── Api/            # 前端接口
│       └── Common/         # 公共组件
├── config/                  # 配置文件
│   └── autoload/           # 自动加载配置
├── databases/              # 数据库
│   ├── migrations/         # 数据库迁移
│   └── seeders/            # 数据填充
├── storage/                # 存储目录
├── runtime/                # 运行时文件
├── web/                    # 前端代码
└── composer.json           # Composer 配置
```

## 核心概念

### DDD 分层

- **Interface Layer**: 处理 HTTP 请求，参数验证，返回响应
- **Application Layer**: 协调领域服务，实现业务流程，CQRS 模式
- **Domain Layer**: 核心业务逻辑，领域模型，业务规则
- **Infrastructure Layer**: 技术实现，数据持久化，外部服务

### CQRS 模式

- **Command Service**: 处理写操作（创建、更新、删除）
- **Query Service**: 处理读操作（查询、统计）
- **Assembler**: 数据转换和组装

### 事件驱动

- **领域事件**: 业务状态变化时发布事件
- **事件监听**: 异步处理业务逻辑
- **解耦**: 降低模块间的耦合度

## 下一步

- [安装部署](/guide/installation) - 了解如何安装和部署系统
- [配置说明](/guide/configuration) - 了解系统配置
- [DDD 架构](/architecture/ddd) - 深入了解架构设计
