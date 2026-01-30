# 安装部署

本文档将指导你完成系统的安装和部署。

## 环境准备

### 1. 系统要求

- **操作系统**: Linux / macOS / Windows
- **PHP**: >= 8.2
- **Swoole**: >= 5.0
- **MySQL**: >= 8.0
- **Redis**: >= 6.0
- **Composer**: >= 2.0

### 2. 安装 PHP 和扩展

#### Linux (Ubuntu/Debian)

```bash
# 添加 PHP 仓库
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# 安装 PHP 8.2
sudo apt install php8.2-cli php8.2-fpm php8.2-mysql php8.2-redis \
  php8.2-curl php8.2-mbstring php8.2-xml php8.2-zip php8.2-bcmath

# 安装 Swoole
sudo pecl install swoole

# 启用 Swoole
echo "extension=swoole.so" | sudo tee /etc/php/8.2/cli/conf.d/20-swoole.ini
```

#### macOS

```bash
# 使用 Homebrew 安装
brew install php@8.2

# 安装 Swoole
pecl install swoole

# 安装 Redis 扩展
pecl install redis
```

#### Windows

推荐使用 Docker 或 WSL2 进行开发。

### 3. 安装 MySQL

```bash
# Ubuntu/Debian
sudo apt install mysql-server-8.0

# macOS
brew install mysql@8.0

# 启动 MySQL
sudo systemctl start mysql  # Linux
brew services start mysql   # macOS
```

### 4. 安装 Redis

```bash
# Ubuntu/Debian
sudo apt install redis-server

# macOS
brew install redis

# 启动 Redis
sudo systemctl start redis  # Linux
brew services start redis   # macOS
```

### 5. 安装 Composer

```bash
# 下载 Composer
curl -sS https://getcomposer.org/installer | php

# 移动到全局
sudo mv composer.phar /usr/local/bin/composer

# 验证安装
composer --version
```

## 安装步骤

### 1. 克隆项目

```bash
# 克隆代码仓库
git clone <repository-url> mine-shop
cd mine-shop
```

### 2. 安装依赖

```bash
# 安装 PHP 依赖
composer install

# 如果速度慢，可以使用国内镜像
composer config repo.packagist composer https://mirrors.aliyun.com/composer/
composer install
```

### 3. 配置环境变量

```bash
# 复制环境变量文件
cp .env.example .env

# 编辑环境变量
vim .env
```

配置示例：

```bash
# 应用配置
APP_NAME=MineShop
APP_ENV=production
APP_DEBUG=false

# 数据库配置
DB_DRIVER=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mine_shop
DB_USERNAME=root
DB_PASSWORD=your_password
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX=

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_AUTH=
REDIS_PORT=6379
REDIS_DB=0

# 应用 URL
APP_URL=http://127.0.0.1:9501

# JWT 密钥（保持默认或生成新的）
JWT_SECRET=azOVxsOWt3r0ozZNz8Ss429ht0T8z6OpeIJAIwNp6X0xqrbEY2epfIWyxtC1qSNM8eD6/LQ/SahcQi2ByXa/2A==
```

### 4. 创建数据库

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE mine_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 退出
exit;
```

### 5. 运行数据库迁移

```bash
# 执行迁移
php bin/hyperf.php migrate

# 如果需要重置数据库
php bin/hyperf.php migrate:fresh
```

### 6. 填充初始数据（可选）

```bash
# 运行数据填充
php bin/hyperf.php db:seed
```

### 7. 启动服务

```bash
# 启动 Hyperf 服务
php bin/hyperf.php start

# 或使用热重载模式（开发环境）
php bin/hyperf.php server:watch
```

服务启动后，访问 `http://127.0.0.1:9501` 即可。

## Docker 部署

### 1. 使用 Docker Compose

项目已包含 `docker-compose.yml` 文件，可以快速启动所有服务。

```bash
# 启动所有服务
docker-compose up -d

# 查看日志
docker-compose logs -f

# 停止服务
docker-compose down
```

### 2. Docker Compose 配置

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    ports:
      - "9501:9501"
    volumes:
      - .:/var/www
    environment:
      - DB_HOST=mysql
      - REDIS_HOST=redis
    depends_on:
      - mysql
      - redis

  mysql:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: mine_shop
    ports:
      - "3306:3306"
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:6.0-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

volumes:
  mysql_data:
  redis_data:
```

### 3. 进入容器执行命令

```bash
# 进入应用容器
docker-compose exec app bash

# 执行迁移
php bin/hyperf.php migrate

# 填充数据
php bin/hyperf.php db:seed
```

## 生产环境部署

### 1. 使用 Supervisor 管理进程

安装 Supervisor：

```bash
# Ubuntu/Debian
sudo apt install supervisor

# macOS
brew install supervisor
```

创建配置文件 `/etc/supervisor/conf.d/mine-shop.conf`：

```ini
[program:mine-shop]
command=php /var/www/mine-shop/bin/hyperf.php start
directory=/var/www/mine-shop
user=www-data
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/supervisor/mine-shop.log
```

启动服务：

```bash
# 重新加载配置
sudo supervisorctl reread
sudo supervisorctl update

# 启动服务
sudo supervisorctl start mine-shop

# 查看状态
sudo supervisorctl status mine-shop
```

### 2. 使用 Systemd 管理服务

创建服务文件 `/etc/systemd/system/mine-shop.service`：

```ini
[Unit]
Description=Mine Shop Service
After=network.target

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/var/www/mine-shop
ExecStart=/usr/bin/php /var/www/mine-shop/bin/hyperf.php start
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

启动服务：

```bash
# 重新加载 systemd
sudo systemctl daemon-reload

# 启动服务
sudo systemctl start mine-shop

# 设置开机自启
sudo systemctl enable mine-shop

# 查看状态
sudo systemctl status mine-shop
```

### 3. 配置 Nginx 反向代理

创建 Nginx 配置文件 `/etc/nginx/sites-available/mine-shop`：

```nginx
upstream mine_shop {
    server 127.0.0.1:9501;
}

server {
    listen 80;
    server_name your-domain.com;

    access_log /var/log/nginx/mine-shop-access.log;
    error_log /var/log/nginx/mine-shop-error.log;

    location / {
        proxy_pass http://mine_shop;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # WebSocket 支持
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }

    # 静态文件
    location /storage {
        alias /var/www/mine-shop/storage;
        expires 30d;
    }
}
```

启用配置：

```bash
# 创建软链接
sudo ln -s /etc/nginx/sites-available/mine-shop /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl restart nginx
```

### 4. 配置 HTTPS（可选）

使用 Let's Encrypt 免费证书：

```bash
# 安装 Certbot
sudo apt install certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d your-domain.com

# 自动续期
sudo certbot renew --dry-run
```

## 性能优化

### 1. OPcache 配置

编辑 `/etc/php/8.2/cli/php.ini`：

```ini
[opcache]
opcache.enable=1
opcache.enable_cli=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0
opcache.save_comments=1
opcache.fast_shutdown=1
```

### 2. Swoole 配置优化

编辑 `config/autoload/server.php`：

```php
return [
    'servers' => [
        [
            'name' => 'http',
            'type' => Server::SERVER_HTTP,
            'host' => '0.0.0.0',
            'port' => 9501,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                Event::ON_REQUEST => [Hyperf\HttpServer\Server::class, 'onRequest'],
            ],
            'settings' => [
                'worker_num' => swoole_cpu_num() * 2,
                'max_request' => 100000,
                'open_tcp_nodelay' => true,
                'max_coroutine' => 100000,
                'enable_coroutine' => true,
            ],
        ],
    ],
];
```

### 3. 数据库连接池优化

编辑 `config/autoload/databases.php`：

```php
'pool' => [
    'min_connections' => 10,
    'max_connections' => 100,
    'connect_timeout' => 10.0,
    'wait_timeout' => 3.0,
    'heartbeat' => -1,
    'max_idle_time' => 60,
],
```

## 常见问题

### 1. Swoole 扩展未安装

```bash
# 检查 Swoole
php --ri swoole

# 如果未安装
pecl install swoole
```

### 2. 端口被占用

```bash
# 查看端口占用
lsof -i :9501

# 修改端口
vim config/autoload/server.php
```

### 3. 权限问题

```bash
# 设置目录权限
sudo chown -R www-data:www-data /var/www/mine-shop
sudo chmod -R 755 /var/www/mine-shop
sudo chmod -R 777 /var/www/mine-shop/runtime
sudo chmod -R 777 /var/www/mine-shop/storage
```

### 4. 数据库连接失败

检查以下配置：
- 数据库服务是否启动
- 数据库用户名和密码是否正确
- 数据库是否已创建
- 防火墙是否允许连接

## 下一步

- [配置说明](/guide/configuration) - 了解详细的配置选项
- [DDD 架构](/architecture/ddd) - 了解系统架构
- [API 文档](/api/) - 查看 API 接口文档
