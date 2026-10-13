# 系统消息插件

提供站内消息、消息模板、通知偏好与发送日志。五张表的唯一建表来源都在本插件 `Database/Migrations/`：`system_messages`、`user_messages`、`message_templates`、`user_notification_preferences`、`message_delivery_logs`。

## 全新部署

```sh
php bin/hyperf.php migrate --force
php bin/hyperf.php db:seed --force
```

项目的 `InstalledPluginMigratorFactory` 将已安装插件目录注册给 Hyperf。标准 `migrate` 与 `migrate:status` 能看到本插件迁移；所有核心和插件文件按原始文件名统一排序，数据库 `migrations` 表负责防止重复执行。`install.lock` 仅表示插件随包安装，不能用它判断数据库是否建表。禁用已安装插件仍会迁移其结构，未安装插件不自动建表。

安装器的目录读取兼容 `Database/Migrations`、`Database/Seeders` 与小写的 `migrations`、`seeders`。本插件迁移采用 `Database/Migrations` 规范目录。真正未安装的插件仍用 `php bin/hyperf.php plugin:install since/system-message`；已经随包安装的插件不必强制重装来建表。

迁移面向空数据库：通知偏好的旧建表和变更文件已合并成最终结构，不执行历史数据转换。系统消息包含渠道扩展 `extra_data`；发送日志支持领域枚举中的全部渠道，包括默认的 `database`。全部业务表及字段都有数据库备注。卸载默认不回滚数据；手工数据库回滚会删除对应表。

## 回归

```sh
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/PluginExtraction
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.<id>/mysql.sock php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/PluginExtraction
```

测试在专用 MySQL 服务中为每个用例创建随机空库，执行实际 Hyperf 迁移命令和插件安装命令，验证未读查询、标记已读、扩展数据、各渠道日志及通知偏好写入，并检查数据库备注。测试不读取 `.env` 或访问业务数据库。
