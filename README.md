中文

# MineShop 商城系统

<p align="center">
    <img src="web/public/logo.svg" width="120" alt="MineShop Logo" />
</p>
<p align="center">
    <a href="https://mineshop.club" target="_blank">官网</a> |
    <a href="https://mineshop.club" target="_blank">文档</a> | 
    <a href="https://demo.mineshop.club" target="_blank">演示</a> |
    <a href="https://hyperf.wiki/3.0/#/" target="_blank">Hyperf官方文档</a> 
</p>

<p align="center">
    <strong>⚠️ 本项目处于开发阶段，暂不建议用于生产环境 ⚠️</strong>
</p>

## 项目介绍

MineShop 是一款基于 MineAdmin 开发的高性能商城系统，采用 DDD（领域驱动设计）架构，为中小企业和个人开发者提供完整的电商解决方案。

### 核心特性

- **高性能架构**：基于 Hyperf + Swoole 协程框架，性能媲美静态语言
- **DDD 架构**：领域驱动设计，代码结构清晰，易于维护和扩展
- **多端支持**：
  - 后台管理：Vue3 + Vite + Element Plus，响应式设计适配 PC/平板/移动端
  - 小程序端：微信小程序原生开发，流畅的用户体验
- **完整功能**：商品管理、订单管理、会员管理、营销工具、数据统计等
- **开箱即用**：无需复杂配置，快速搭建属于你的电商平台

### 技术栈

**后端**
- Hyperf 3.1 - 企业级协程框架
- PHP 8.1+ - 现代 PHP 特性
- Swoole 5.0+ - 高性能协程引擎
- MySQL/PostgreSQL - 数据存储
- Redis - 缓存与队列

**前端**
- Vue 3 - 渐进式 JavaScript 框架
- Vite 4 - 下一代前端构建工具
- Element Plus - Vue 3 组件库
- Pinia - 状态管理

**小程序**
- 微信小程序原生开发

如果觉着还不错的话，就请点个 ⭐star 支持一下吧，这将是对我最大的支持和鼓励！

**⚠️ 重要提示：使用 MineShop 前请务必认真阅读[《免责声明》](#️-重要声明)并同意该声明。**

## 官方交流群
> QQ群用于交流学习，请勿水群

<a href="https://qm.qq.com/q/PJnEgr4D8C">
  <img src="https://svg.hamm.cn/badge.svg?key=QQ群&value=1087473106" />
</a>

## 功能模块

### 商城核心功能
1. **商品管理**：商品分类、品牌管理、商品 SKU、规格管理、库存管理
2. **订单管理**：订单列表、订单详情、订单状态流转、发货管理、退款管理
3. **会员管理**：会员档案、会员等级、会员标签、积分管理、余额管理
4. **营销工具**：
   - 优惠券：满减券、折扣券、优惠券发放与核销
   - 秒杀活动：限时秒杀、库存管理、秒杀场次
   - 拼团活动：拼团商品、成团规则、拼团订单
5. **支付管理**：微信支付、余额支付、支付回调处理
6. **物流管理**：运费模板、发货管理、物流跟踪

### 后台管理功能
1. **权限管理**：用户管理、角色管理、菜单管理、数据权限
2. **系统设置**：商城配置、支付配置、物流配置、系统参数
3. **日志管理**：操作日志、登录日志、系统日志
4. **附件管理**：图片上传、文件管理、存储配置
5. **组织架构**：部门管理、岗位管理
6. **数据统计**：订单统计、商品统计、会员统计、销售报表

## 环境需求

- Swoole >= 5.0 并关闭 `Short Name`
- PHP >= 8.1 并开启以下扩展：
  - mbstring
  - json
  - pdo
  - openssl
  - redis
  - pcntl
- [x] Mysql >= 5.7
- [x] Pgsql >= 10
- [x] Sql Server Latest
- Sqlsrv is Latest
- Redis >= 4.0
- Git >= 2.x


## 快速开始

### 环境准备
确保你的开发环境已安装：
- PHP >= 8.1
- Composer
- Node.js >= 16
- MySQL >= 5.7 或 PostgreSQL >= 10
- Redis >= 4.0

### 安装步骤

1. **克隆项目**
```shell
git clone https://github.com/your-repo/mineshop.git
cd mineshop
```

2. **安装后端依赖**
```shell
composer install
```

3. **配置环境变量**
```shell
cp .env.example .env
# 编辑 .env 文件，配置数据库、Redis 等信息
```

4. **运行数据库迁移**
```shell
php bin/hyperf.php migrate
php bin/hyperf.php db:seed
```

5. **启动后端服务**
```shell
php bin/hyperf.php start
```

6. **安装前端依赖并启动**
```shell
cd web
npm install
npm run dev
```

7. **访问系统**
- 后台管理：http://localhost:5173
- 默认账号：admin
- 默认密码：123456

### 小程序配置
1. 在微信公众平台注册小程序
2. 配置小程序 AppID 和 AppSecret
3. 使用微信开发者工具导入 `shopProgramMini` 目录
4. 修改小程序配置文件中的 API 地址

## 体验地址

**后台管理**
- 地址：https://demo.mineshop.club
- 账号：admin
- 密码：123456

**小程序演示(暂未部署)**
- 微信扫码体验（二维码）
- 仓库地址：https://github.com/since-leoo/mine-shop-miniprogram

> 请勿添加脏数据

## 项目结构

```
mineshop/
├── app/                      # 应用代码
│   ├── Application/          # 应用层（API、命令、查询服务）
│   ├── Domain/              # 领域层（实体、值对象、领域服务）
│   ├── Infrastructure/      # 基础设施层（数据库模型、仓储实现）
│   └── Interface/           # 接口层（控制器、DTO、请求验证）
├── web/                     # 后台管理前端（Vue3）
├── config/                  # 配置文件
├── migrations/              # 数据库迁移文件
├── storage/                 # 存储目录
└── bin/                     # 可执行文件

```

## ⚠️ 重要声明

### 开发阶段提示
**本项目目前处于开发测试阶段，不建议投入生产环境使用！**

- ✅ 适用于：学习研究、技术交流、功能测试
- ❌ 不适用于：生产环境、商业项目、正式运营

### 免责声明

1. **使用风险**：本软件按"原样"提供，不提供任何明示或暗示的保证。使用本软件的所有风险由使用者自行承担。

2. **生产环境**：若在生产环境中使用本软件导致的任何问题（包括但不限于数据丢失、业务中断、经济损失等），开发者及贡献者概不负责。

3. **法律合规**：使用本软件不得用于开发违反国家法律法规、政策的相关软件和应用。若因使用本软件造成的一切法律责任均与 `MineShop` 及其开发者无关。

4. **商业使用**：本软件仅供学习交流使用。如需商业使用，请自行评估风险并做好充分测试。

5. **安全责任**：使用者应自行负责系统的安全防护、数据备份、漏洞修复等工作。

6. **无担保**：开发者不对软件的适用性、可靠性、准确性做任何承诺或担保。

**使用本软件即表示您已阅读、理解并同意上述声明。如不同意，请勿使用本软件。**

## 技术支持

如遇到问题，可通过以下方式获取帮助：
- 提交 Issue：https://github.com/since-leoo/mine-shop/issues
- 加入 QQ 1087473106
- 访问官网：https://www.mineshop.club

## 鸣谢

> 以下排名不分先后

- [MineAdmin](https://www.mineadmin.com) - 基础框架支持
- [Hyperf](https://hyperf.io/) - 高性能企业级协程框架
- [Swoole](https://www.swoole.com) - PHP 协程引擎
- [Vue.js](https://vuejs.org/) - 渐进式 JavaScript 框架
- [Element Plus](https://element-plus.org/) - Vue 3 组件库
- [Vite](https://vitejs.cn/) - 下一代前端构建工具
- [Jetbrains](https://www.jetbrains.com/) - 生产力工具

## 开源协议

本项目基于 [Apache-2.0](LICENSE) 协议开源。

## Star 趋势

如果这个项目对你有帮助，请给我们一个 ⭐ Star 支持！

## 贡献者

感谢所有参与 MineShop 开发的贡献者！

欢迎提交 Pull Request 或 Issue 来帮助改进项目。

## 演示图片
[![pAdQKPJ.png](https://s21.ax1x.com/2024/10/22/pAdQKPJ.png)](https://imgse.com/i/pAdQKPJ)
[![pAdQlx1.png](https://s21.ax1x.com/2024/10/22/pAdQlx1.png)](https://imgse.com/i/pAdQlx1)
[![pAdQQ2R.png](https://s21.ax1x.com/2024/10/22/pAdQQ2R.png)](https://imgse.com/i/pAdQQ2R)
[![pAdQGqK.png](https://s21.ax1x.com/2024/10/22/pAdQGqK.png)](https://imgse.com/i/pAdQGqK)
[![pAdQYVO.png](https://s21.ax1x.com/2024/10/22/pAdQYVO.png)](https://imgse.com/i/pAdQYVO)
[![pAdQNIe.png](https://s21.ax1x.com/2024/10/22/pAdQNIe.png)](https://imgse.com/i/pAdQNIe)
[![pAdQaPH.png](https://s21.ax1x.com/2024/10/22/pAdQaPH.png)](https://imgse.com/i/pAdQaPH)
[![pAdQdGd.png](https://s21.ax1x.com/2024/10/22/pAdQdGd.png)](https://imgse.com/i/pAdQdGd)
