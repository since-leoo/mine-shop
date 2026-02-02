# 安装部署指南

本文涵盖 Mine Shop 后端（Hyperf）、前端（Vue 3）、Geo 地址库同步以及 Docker/生产部署流程。默认仓库路径：`/mnt/d/PhpstormProjects/mine-shop`。

## 1. 环境准备

| 组件 | 推荐版本 |
| ---- | -------- |
| PHP | 8.1.x with CLI |
| Swoole | 5.0+（通过 PECL 安装） |
| MySQL | 8.0+ |
| Redis | 6.0+ |
| Composer | 2.x |
| Node.js | 20.x（用于前端与文档） |
| npm/pnpm | 任一包管理器均可 |

启用 PHP 扩展：`swoole`, `redis`, `pdo_mysql`, `mbstring`, `json`, `openssl`, `bcmath`, `pcntl`, `posix`。

### Linux/macOS（示例）

```bash
# Ubuntu
sudo apt update
sudo apt install php8.1-cli php8.1-dev php8.1-mysql php8.1-redis \
  php8.1-mbstring php8.1-xml php8.1-zip php8.1-bcmath
sudo pecl install swoole
echo "extension=swoole.so" | sudo tee /etc/php/8.1/mods-available/swoole.ini
sudo phpenmod swoole

# macOS
brew install php@8.1 redis mysql
pecl install swoole redis
```

Windows 建议使用 WSL2 或 Docker。

## 2. 克隆与依赖

```bash
git clone <repository-url> mine-shop
cd mine-shop
composer install
```

若需要国内镜像：`composer config repo.packagist composer https://mirrors.aliyun.com/composer/`。

## 3. 配置 `.env`

```bash
cp .env.example .env
```

关键参数：

```dotenv
APP_NAME=MineShop
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:9501

DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mine_shop
DB_USERNAME=root
DB_PASSWORD=secret

REDIS_HOST=127.0.0.1
REDIS_PORT=6379

JWT_SECRET=base64随机字符串
```

如需配置支付、AliOSS、短信、Geo，同样在 `.env` 中补充。

## 4. 初始化数据库

```bash
# 创建数据库
mysql -u root -p -e "CREATE DATABASE mine_shop DEFAULT CHARACTER SET utf8mb4;"

# 迁移 & 种子
php bin/hyperf.php migrate
php bin/hyperf.php db:seed   # 将插入默认账号、菜单等
```

> 默认后台账号/密码可在 `databases/seeders` 中查看并修改。

## 5. 同步 Geo 地址库（可选）

```bash
php bin/hyperf.php mall:sync-regions --source=modood
```

常用参数：`--url`（自定义数据源）、`--force`（覆盖同版本）、`--dry-run`（仅解析）。完成后可通过 `/geo/pcas` 接口获取四级联动数据。

在 `config/autoload/crontab.php` 中可添加定时任务，例如：

```php
return [
    [
        'name' => 'geo-sync',
        'rule' => '30 3 * * *',
        'callback' => [\App\Infrastructure\Command\Geo\SyncGeoRegionsCommand::class, 'handle'],
        'memo' => '每日同步地址库',
    ],
];
```

## 6. 启动 Hyperf 服务

```bash
# 开发模式（热重载）
php bin/hyperf.php server:watch

# 或标准模式
php bin/hyperf.php start
```

默认监听 `http://127.0.0.1:9501`。检查日志 `runtime/logs/` 确认服务启动成功。

## 7. 启动前端（Vue 3 + Element Plus）

```bash
cd web
npm install          # 或 pnpm install
npm run dev          # 默认 http://127.0.0.1:5173
```

如需生产构建：`npm run build`，静态文件默认输出到 `web/dist`。

## 8. 文档站点（可选）

```bash
cd shopDoc
npm install
npm run docs:dev
```

## 9. Docker 部署

项目自带 `docker-compose.yml`，可快速启动 app/mysql/redis：

```bash
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php bin/hyperf.php migrate
```

根据需要映射 `.env`、配置卷与端口。生产环境建议单独管理 MySQL/Redis。

## 10. 生产部署

### Supervisor

`/etc/supervisor/conf.d/mine-shop.conf`：

```ini
[program:mine-shop]
command=/usr/bin/php /var/www/mine-shop/bin/hyperf.php start
directory=/var/www/mine-shop
user=www-data
autostart=true
autorestart=true
stdout_logfile=/var/log/supervisor/mine-shop.log
```

### Systemd（可选）

```ini
[Unit]
Description=Mine Shop Service
After=network.target

[Service]
ExecStart=/usr/bin/php /var/www/mine-shop/bin/hyperf.php start
Restart=always
User=www-data
WorkingDirectory=/var/www/mine-shop

[Install]
WantedBy=multi-user.target
```

### Nginx 反向代理

```nginx
upstream mineship {
    server 127.0.0.1:9501;
}
server {
    listen 80;
    server_name shop.example.com;

    location / {
        proxy_pass http://mineship;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /storage {
        alias /var/www/mine-shop/storage;
    }
}
```

## 11. 常见问题

| 问题 | 排查 |
| ---- | ---- |
| `redis` 扩展未启用 | `php --ri redis` 检查，确保 `extension=redis` 已加载 |
| `swoole` 不支持 | `php --ri swoole`，确认与 PHP 版本匹配 |
| 订单下单库存异常 | 确认 Redis 已启动，`mall:stock:sku:{id}` 初始值正确 |
| Geo 接口报 “尚未同步” | 执行 `mall:sync-regions` 或检查 `geo_region_versions` 表 |
| 前端白屏 | 控制台查看接口地址、跨域配置，确保 Vite 代理到正确 API |

完成以上步骤即可本地体验与部署 Mine Shop。更多配置项请参阅 [配置说明](/guide/configuration)。*** End Patch
