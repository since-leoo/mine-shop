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

## 通知投递配置与状态

通知渠道使用 `system_message` 配置；用户关闭渠道或处于免打扰时不会投递。站内信在确认该用户存在未删除的收件记录后记为 `delivered`。邮件、短信、小程序及推送网关确认接收后记为 `sent`，不把供应商接收等同于用户已收到/已读。缺配置、无收件地址、供应商拒绝或异常记为 `failed`，保留错误原因并发布 `NotificationFailed` 事件。

- 邮件：设置 `system_message.email.from` 为有效邮箱，并在部署环境配置 PHP `mail()` 使用的 MTA。标题拒绝换行注入，正文以转义后的文字渲染 HTML，不依赖外部 `view()` 函数。
- SMS：复用短信插件配置和 `SmsSenderInterface`；未指定 `extra_data.template_code` 时保留短信插件默认模板。
- 小程序：复用微信插件，要求消息明确指定一个后台收件用户，以及对应 `extra_data.openid` / `miniapp_template_id`。只有微信响应 `errcode=0` 才记为已发送，禁止复用单个 openid 群发给多个用户。
- `socketio`、`websocket`、`push`：部署实际投递网关，在 `system_message.gateways.<channel>` 配置 `url`（HTTPS）和 `token`。网关负责将后台用户 ID 映射到连接或设备；本插件不创建另一个 WebSocket 服务。

网关请求为 POST，带 `Authorization: Bearer <token>` 和稳定的 `Idempotency-Key: <channel>:<message_id>:<user_id>`。JSON 格式为 `{"channel":"push","user_id":7,"message":{"message_id":11,"title":"通知","content":"正文","type":"system","priority":1}}`。网关须按幂等键去重，并在接收成功时返回 HTTP 2xx 与 `{"success":true}`。未提供明确回执不算成功。连接超时 3 秒、总超时 5 秒，禁止跟随重定向以避免泄露认证令牌。

通知只由 `SendMessageJob` 排队，渠道方法同步返回结果；渠道故障记录后抛给队列重试，用户偏好或免打扰跳过不重试。消息主记录只有在收件箱写入且全部任务被队列接受后才标记 `sent`；队列拒收会标记 `failed`，允许重发。重发保留已有收件记录与阅读状态，跳过已有成功渠道；重复任务按收件记录行锁串行执行，并复查成功日志。

外部通知仍为至少一次投递：若供应商已接收但进程在成功日志提交前中断，可能重试。HTTP 网关必须遵守上述幂等键约定。最终渠道状态以 `message_delivery_logs` 为准，主记录 `sent` 不表示所有外部渠道送达。

线上配置不填写真实测试收件人；自动化测试使用模拟传输器、模拟 HTTP 响应和临时数据库，不发送短信、邮件、微信或设备推送。
