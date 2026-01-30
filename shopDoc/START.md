# 文档系统启动说明

## 已创建的文档

✅ 文档系统已成功创建！包含以下内容：

### 📚 指南文档
- ✅ 介绍 (`guide/index.md`)
- ✅ 安装部署 (`guide/installation.md`)
- ✅ 配置说明 (`guide/configuration.md`)

### 🏗️ 架构设计
- ✅ DDD 架构 (`architecture/ddd.md`)
- ✅ 分层设计 (`architecture/layers.md`)
- ✅ 设计模式 (`architecture/patterns.md`)

### 💡 核心设计
- ✅ 订单设计 (`core/order-design.md`)
- ✅ 库存管理 (`core/stock-management.md`)
- ✅ 支付系统 (`core/payment.md`)

### 🔌 API 接口
- ✅ API 概览 (`api/index.md`)
- ✅ 后台接口 (`api/admin.md`)
- ✅ 前端接口 (`api/frontend.md`)
- ✅ 认证授权 (`api/auth.md`)

## 启动文档系统

### 1. 安装依赖

```bash
cd shopDoc
npm install
```

### 2. 启动开发服务器

```bash
npm run docs:dev
```

文档将在 `http://localhost:5173` 启动。

### 3. 构建生产版本

```bash
npm run docs:build
```

构建结果在 `.vitepress/dist` 目录。

### 4. 预览构建结果

```bash
npm run docs:preview
```

## 文档特点

### ✨ 完整的内容覆盖

1. **安装部署** - 详细的安装步骤，包括 Docker 部署、生产环境配置
2. **DDD 架构** - 深入讲解四层架构设计和实现
3. **订单设计** - 完整的订单系统设计，包括状态流转、策略模式
4. **库存管理** - 基于 Redis + Lua 的高性能库存管理方案
5. **支付系统** - 支付宝、微信支付、余额支付的完整实现
6. **API 文档** - 后台和前端的完整 API 接口文档

### 🎯 实用的代码示例

- 每个模块都包含实际的代码示例
- 展示了 Lua 脚本的完整实现
- 提供了数据库表结构设计
- 包含了完整的业务流程图

### 📖 清晰的结构

- 从入门到深入，循序渐进
- 架构设计和实现细节分离
- 核心功能独立成章
- API 文档完整规范

## 下一步

### 可以继续完善的内容

1. **功能模块文档** (`features/`)
   - 产品管理详细说明
   - 秒杀功能实现细节
   - 团购功能实现细节
   - 会员系统说明

2. **部署文档**
   - Kubernetes 部署
   - CI/CD 流程
   - 监控和日志

3. **开发指南**
   - 代码规范
   - Git 工作流
   - 测试指南

4. **FAQ 文档**
   - 常见问题解答
   - 故障排查指南

## 文档维护

### 更新文档

1. 编辑对应的 Markdown 文件
2. 保存后开发服务器会自动刷新
3. 提交到 Git 仓库

### 添加新页面

1. 在对应目录创建 `.md` 文件
2. 在 `.vitepress/config.mts` 的 `sidebar` 中添加链接
3. 编写内容

### 添加图片

1. 将图片放在 `public/images/` 目录
2. 在 Markdown 中引用：`![描述](/images/xxx.png)`

## 技术栈

- **VitePress**: 基于 Vite 的静态站点生成器
- **Vue 3**: 前端框架
- **Markdown**: 文档编写格式

## 联系方式

如有问题，请联系开发团队。

---

**祝你使用愉快！** 🎉
