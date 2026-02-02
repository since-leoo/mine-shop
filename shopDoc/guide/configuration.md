# 配置说明

Mine Shop 通过 `.env` + `config/autoload/*.php` 管理运行参数。本页列举常用配置项及其作用，帮助快速定制。

## `.env` 核心变量

```dotenv
# 应用
APP_NAME=MineShop
APP_ENV=prod|local
APP_DEBUG=false
APP_URL=https://api.example.com

# 数据库
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mine_shop
DB_USERNAME=root
DB_PASSWORD=secret

# Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_AUTH=

# JWT
JWT_SECRET=base64:xxxxxxxx
JWT_TTL=7200         # Access Token 秒数
JWT_REFRESH_TTL=604800

# 日志
LOG_CHANNEL=stack
LOG_LEVEL=info
```

## 商城配置 `config/autoload/mall.php`

| 分组 | 关键字段 | 说明 |
| ---- | -------- | ---- |
| `currency` | `code`, `symbol`, `decimal_places` | 货币代码与展示格式 |
| `product` | `auto_approve`, `max_images`, `max_image_size` | 商品默认状态、图片限制 |
| `order` | `auto_confirm_days`, `auto_close_minutes`, `order_no_prefix` | 订单自动确认/关闭、订单号前缀 |
| `member` | `default_level`, `points_ratio` | 会员默认等级、积分兑换比例 |
| `payment` | `methods` | 控制支付宝/微信/余额是否启用，及展示名称 |
| `shipping` | `methods` | 快递/自提等配送策略 |
| `geo` (新增) | `enabled`, `cache_ttl` | Geo 地址缓存配置 |

> 可在后台「系统设置」中通过界面化方式调整，变更将写入数据库 `system_settings`。

## 支付配置 `config/autoload/pay.php`

已在 [支付系统](/core/payment) 章节详述，此处仅补充：

- `mode` 可设置 `normal` / `dev` / `sandbox`。
- Log 文件默认存储在 `runtime/logs/{channel}.log`，建议确保目录可写。

## Geo 地址库 `config/autoload/geo.php` *(如有)*

示例：

```php
return [
    'default_source' => 'modood',
    'cache_ttl' => 86400,
    'cron' => [
        'enabled' => true,
        'rule' => '30 3 * * *',
    ],
];
```

结合命令 `mall:sync-regions` 可控制数据源、自定义 URL 与缓存策略。

## Crontab `config/autoload/crontab.php`

用于配置 Hyperf 内建定时任务。示例：

```php
return [
    [
        'name' => 'geo-sync',
        'rule' => '30 3 * * *',
        'callback' => [\App\Infrastructure\Command\Geo\SyncGeoRegionsCommand::class, 'handle'],
    ],
    [
        'name' => 'order-auto-close',
        'rule' => '*/5 * * * *',
        'callback' => [\App\Application\Order\Task\OrderAutoCloseTask::class, 'handle'],
    ],
];
```

## 队列/异步

如果启用 Hyperf 异步队列，在 `config/autoload/async_queue.php` 或相关配置中设置 Redis 连接、超时、重试次数。

## 日志与审计

`config/autoload/logger.php` 控制日志通道；常见通道：

- `default`：应用日志。
- `sql`：慢 SQL 记录。
- `operation`：操作日志（配合 `UserOperationLogService`）。

可将日志投递到 `stdout`、文件或 ELK。

## 缓存

默认使用 Redis（`config/autoload/cache.php`）。Geo、会员概览、权限菜单等都使用 PSR-16 接口。可按需切换 Memcached/文件。

## 任务队列

Hyperf 支持 `crontab` + `async-queue`。如要启用异步任务，请在 `.env` 中配置：

```dotenv
ASYNC_QUEUE_DRIVER=redis
ASYNC_QUEUE_CONSUMERS=2
```

## 第三方服务

视项目需求启用以下配置文件：

| 文件 | 作用 |
| ---- | ---- |
| `config/autoload/oss.php` | 对象存储（OSS/OBS/COS） |
| `config/autoload/wechat.php` | 微信小程序/公众号参数 |
| `config/autoload/mail.php` | 邮件通知 |
| `config/autoload/attachment.php` | 附件策略（本地/云） |

## 调试开关

- `APP_DEBUG=true` 可输出详细错误，适用于开发环境。
- `DB_LOG=true` 记录 SQL，配合 `LOG_LEVEL=debug` 使用。
- `ENABLE_HTTP_SERVER=false` 可在命令模式下禁用 HTTP Server（例如仅跑 CLI）。

## 生产加固建议

1. 设置 `APP_ENV=prod`、`APP_DEBUG=false`，关闭报错输出。
2. 为 Hyperf HTTP Server 配置 Nginx/Ingress 代理，开启 HTTPS。
3. 为 `.env`、`storage`、`runtime` 目录设置正确权限。
4. 使用 Supervisor/Systemd 管理进程，防止异常退出。
5. 配置 Geo、库存、订单等关键任务的监控与告警。
6. 备份数据库与 Redis，定期执行 `mall:sync-regions --dry-run` 验证外部数据源。

更多架构与实现细节见 [DDD 架构](/architecture/ddd) 与 [核心设计](/core/order-design)。*** End Patch
