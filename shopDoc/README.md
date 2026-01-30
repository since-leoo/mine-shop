# 商城系统文档

欢迎使用商城系统文档！

## 快速开始

- [安装部署](/guide/installation) - 了解如何安装和部署系统
- [配置说明](/guide/configuration) - 了解系统配置
- [DDD 架构](/architecture/ddd) - 了解系统架构设计

## 本地运行文档

```bash
# 进入文档目录
cd shopDoc

# 安装依赖
npm install

# 启动开发服务器
npm run docs:dev

# 构建文档
npm run docs:build

# 预览构建结果
npm run docs:preview
```

## 文档结构

```
shopDoc/
├── guide/              # 指南
│   ├── index.md       # 介绍
│   ├── installation.md # 安装部署
│   └── configuration.md # 配置说明
├── architecture/       # 架构设计
│   ├── ddd.md         # DDD 架构
│   ├── layers.md      # 分层设计
│   └── patterns.md    # 设计模式
├── features/          # 功能模块
│   ├── product.md     # 产品管理
│   ├── order.md       # 订单系统
│   ├── seckill.md     # 秒杀功能
│   └── group-buy.md   # 团购功能
├── core/              # 核心设计
│   ├── order-design.md # 订单设计
│   ├── stock-management.md # 库存管理
│   └── payment.md     # 支付系统
└── api/               # API 接口
    ├── index.md       # API 概览
    ├── admin.md       # 后台接口
    └── frontend.md    # 前端接口
```

## 贡献文档

欢迎贡献文档！请遵循以下步骤：

1. Fork 本项目
2. 创建文档分支
3. 编写或修改文档
4. 提交 Pull Request

## 许可证

MIT License
