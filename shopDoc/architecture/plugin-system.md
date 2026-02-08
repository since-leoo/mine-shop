# 插件系统

Mine Shop 采用自研插件管理器 `since-leoo/hyperf-plugin`，实现业务模块的热插拔。插件以独立目录存在于 `plugins/` 下，拥有完整的 DDD 四层结构，通过标准化的生命周期管理与 Hyperf 框架深度集成。

## 设计理念

传统单体应用中，营销活动（秒杀、拼团、优惠券）、运费计算、地理服务等模块与核心订单逻辑紧耦合，导致：

- 新增/移除功能需要修改核心代码
- 模块间依赖关系混乱，牵一发而动全身
- 不同项目复用困难

插件系统从根本上解决了这些问题。核心应用只定义接口契约，插件提供具体实现，通过 Hyperf 的 DI 容器自动装配。**拔掉插件，系统照常运行；插上插件，能力即刻扩展。**

## 架构总览

```
bin/hyperf.php
  │
  ├── require vendor/autoload.php
  ├── PluginBootstrap::init()          ← 插件引导（Composer 之后、DI 之前）
  │     ├── 扫描 plugins/ 目录
  │     ├── 读取 plugin.json 配置
  │     ├── 检查 enabled + install.lock
  │     ├── 注册 PSR-4 自动加载
  │     ├── 注入 ConfigProvider 到 Composer extra
  │     └── 加载 Helper 文件
  └── ClassLoader::init()              ← Hyperf DI 容器初始化
        └── ProviderConfig::load()     ← 自动发现插件的 ConfigProvider
```

关键设计：`PluginBootstrap::init()` 在 Hyperf `ClassLoader::init()` **之前**执行。这意味着当 Hyperf 的注解扫描和依赖注入容器启动时，插件的类已经可以被发现、插件的 ConfigProvider 已经被注册。整个过程对 Hyperf 框架完全透明，无需任何框架层面的修改。

## PluginBootstrap 引导流程

```php
// bin/hyperf.php
require BASE_PATH . '/vendor/autoload.php';
\SinceLeoo\Plugin\PluginBootstrap::init();   // 插件引导
\Hyperf\Di\ClassLoader::init();               // Hyperf DI
```

`PluginBootstrap::init()` 的核心逻辑：

1. **解析插件目录** — 从 `config/autoload/plugins.php` 读取 `plugins_path`，默认 `plugins`
2. **扫描插件** — 遍历插件目录，读取每个插件的 `plugin.json`
3. **准入检查** — 必须同时满足：`enabled: true` + 存在 `install.lock` 文件
4. **PSR-4 注册** — 通过 Composer ClassLoader 注册插件命名空间到 `src/` 目录
5. **ConfigProvider 注入** — 利用反射将插件的 ConfigProvider 写入 `Hyperf\Support\Composer::$extra`，使 `ProviderConfig::load()` 能自动发现
6. **Helper 加载** — 自动 require `src/Helper/helper.php`（如果存在）

这套机制的精妙之处在于：**不修改 Composer 的 `composer.json`，不修改 Hyperf 的任何配置文件**，却能让插件的类、配置、依赖绑定全部生效。

## plugin.json 配置规范

每个插件根目录必须包含 `plugin.json`：

```json
{
    "name": "since/seckill",
    "version": "1.0.0",
    "description": "秒杀活动模块 - 支持活动/场次/商品管理、限时抢购、库存扣减",
    "author": "Since",
    "namespace": "Plugin\\Since\\Seckill",
    "priority": 10,
    "enabled": true,
    "dependencies": [],
    "composer_require": {},
    "rollback_on_uninstall": true,
    "configProvider": "Plugin\\Since\\Seckill\\ConfigProvider"
}
```

| 字段 | 必填 | 说明 |
|------|------|------|
| `name` | ✅ | 插件标识，格式 `vendor/name` |
| `namespace` | ✅ | PSR-4 根命名空间 |
| `enabled` | ✅ | 是否启用 |
| `configProvider` | ✅ | ConfigProvider 完整类名（camelCase） |
| `version` | 否 | 语义化版本号 |
| `description` | 否 | 插件描述 |
| `priority` | 否 | 加载优先级（数值越小越先加载） |
| `dependencies` | 否 | 依赖的其他插件 |
| `composer_require` | 否 | 额外 Composer 依赖 |
| `rollback_on_uninstall` | 否 | 卸载时是否回滚数据库迁移 |

> ⚠️ `configProvider` 必须使用 camelCase，不要写成 `config_provider`。

## 插件生命周期

每个插件包含一个 `Plugin.php`，继承 `SinceLeoo\Plugin\Contract\AbstractPlugin`：

```php
class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        // 安装时执行：数据库迁移、初始数据填充等
    }

    public function uninstall(): void
    {
        // 卸载时执行：清理资源
    }

    public function boot(): void
    {
        // 每次启动时执行：注册策略、绑定事件等
    }
}
```

| 生命周期 | 触发时机 | 典型用途 |
|----------|----------|----------|
| `install()` | 插件安装时 | 执行数据库迁移、填充初始数据 |
| `uninstall()` | 插件卸载时 | 回滚迁移、清理缓存 |
| `boot()` | 每次应用启动 | 注册订单策略、绑定监听器 |

## 插件目录结构

插件遵循与主应用一致的 DDD 四层架构：

```
plugins/seckill/
├── plugin.json              # 插件配置
├── install.lock             # 安装锁（存在才会加载）
└── src/
    ├── Plugin.php           # 生命周期入口
    ├── ConfigProvider.php   # Hyperf 配置提供者
    ├── Domain/              # 领域层
    │   ├── Entity/          # 实体
    │   ├── Repository/      # 仓储接口
    │   ├── Service/         # 领域服务
    │   ├── Strategy/        # 订单策略
    │   └── ValueObject/     # 值对象
    ├── Infrastructure/      # 基础设施层
    │   ├── Model/           # ORM Model
    │   └── Repository/      # 仓储实现
    ├── Application/         # 应用层
    │   └── Service/         # 应用服务
    └── Interface/           # 接口层
        └── Controller/      # 控制器
```

## 接口解耦模式

这是插件系统最核心的设计模式。主应用定义接口契约，插件提供适配器实现，通过 ConfigProvider 的 `dependencies` 注册绑定。

### 示例：优惠券服务

**主应用定义接口**（`app/Domain/Trade/Order/Contract/`）：

```php
interface CouponServiceInterface
{
    public function findUsableCoupon(int $memberId, int $couponId): ?array;
    public function settleCoupon(int $couponUserId, int $orderId): void;
}
```

**插件提供适配器**（`plugins/coupon/src/Domain/Service/`）：

```php
class CouponServiceAdapter implements CouponServiceInterface
{
    public function __construct(
        private readonly CouponUserRepository $couponUserRepository,
    ) {}

    public function findUsableCoupon(int $memberId, int $couponId): ?array { /* ... */ }
    public function settleCoupon(int $couponUserId, int $orderId): void { /* ... */ }
}
```

**插件 ConfigProvider 注册绑定**：

```php
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                CouponServiceInterface::class => CouponServiceAdapter::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [__DIR__],
                ],
            ],
        ];
    }
}
```

这样，`NormalOrderStrategy` 只依赖 `CouponServiceInterface`，完全不知道优惠券插件的存在。卸载优惠券插件后，只需提供一个空实现或在策略中做 null 检查即可。

### 当前接口契约

| 接口 | 所在位置 | 插件实现 |
|------|----------|----------|
| `CouponServiceInterface` | `app/Domain/Trade/Order/Contract/` | `plugins/coupon` → `CouponServiceAdapter` |
| `FreightServiceInterface` | `app/Domain/Trade/Order/Contract/` | `plugins/shipping` → `FreightServiceAdapter` |

## 策略动态注册

秒杀、拼团等订单策略通过插件的 `boot()` 方法动态注册到 `OrderTypeStrategyFactory`：

```php
// plugins/seckill/src/Plugin.php
public function boot(): void
{
    $container = ApplicationContext::getContainer();
    $factory = $container->get(OrderTypeStrategyFactory::class);
    $factory->register($container->get(SeckillOrderStrategy::class));
}
```

主应用的 `dependencies.php` 只注册 `NormalOrderStrategy`，其余策略由各插件自行注册。这意味着：

- 安装秒杀插件 → 自动支持秒杀订单
- 卸载秒杀插件 → 秒杀能力自动移除，不影响普通订单

## 已有插件一览

| 插件 | 目录 | 说明 |
|------|------|------|
| `since/seckill` | `plugins/seckill` | 秒杀活动 — 活动/场次/商品管理、限时抢购、独立库存扣减 |
| `since/group-buy` | `plugins/group-buy` | 拼团活动 — 开团/参团/成团判定、团购价计算 |
| `since/coupon` | `plugins/coupon` | 优惠券 — 券模板/发放/核销、满减/折扣计算 |
| `since/shipping` | `plugins/shipping` | 运费计算 — 运费模板、阶梯计费、偏远地区加价 |
| `since/geo` | `plugins/geo` | 地理服务 — 省市区数据、地址解析 |
| `since/system-message` | `plugins/system-message` | 系统消息 — 站内信、消息模板 |
| `since/wechat` | `plugins/wechat` | 微信集成 — 小程序登录、微信支付回调 |

## 开发新插件

1. 在 `plugins/` 下创建插件目录
2. 编写 `plugin.json`，确保 `namespace`、`enabled`、`configProvider` 正确
3. 创建 `src/Plugin.php` 继承 `AbstractPlugin`
4. 创建 `src/ConfigProvider.php`，注册注解扫描路径和依赖绑定
5. 按 DDD 四层组织代码
6. 创建 `install.lock` 文件
7. 重启应用，插件自动生效

```bash
mkdir -p plugins/my-plugin/src
touch plugins/my-plugin/install.lock
# 编写 plugin.json、Plugin.php、ConfigProvider.php
# 重启 Hyperf 即可
```
