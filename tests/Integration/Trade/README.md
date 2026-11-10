# 交易可靠性回归

从仓库根目录运行全部隔离测试：

```bash
bash tests/run-isolated.sh
```

脚本创建独立的 `/tmp/mine-shop-tests.*` MySQL 数据目录和 Redis socket，禁用 TCP，不加载应用 `.env`。每个数据库测试创建并销毁随机数据库；Redis 测试只清理自己生成的随机 key。脚本退出时停止测试服务并保留日志与 JUnit 报告。需要本机 PHP 8.3、pdo_mysql、redis、MySQL 和 Redis；可用 `MYSQLD_BIN` / `REDIS_SERVER_BIN` 指定安装路径。

已经启动隔离服务时可单独跑交易测试：

```bash
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/Trade
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.EXAMPLE/mysql.sock \
MINE_SHOP_TEST_REDIS_SOCKET=/tmp/mine-shop-tests.EXAMPLE/redis.sock \
php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Trade
```

测试入口会拒绝业务数据库地址。`trade-concurrency-worker.php` 仅允许访问这类 socket 下的 `codex_test_*` 随机数据库。余额竞争、支付与取消竞争使用两个独立 PHP 进程和独立 MySQL 连接，实际执行领域服务与行锁。

覆盖内容：

- 微信预支付使用真实订单号、会员 openid 和分金额；本地生成 RSA 签名与 AES-GCM 密文，执行已安装 SDK 的验签、解密，验证 `resource.ciphertext` 适配以及篡改拒绝。未请求真实微信渠道。
- 余额归属、开关、累计日限额、可用余额和钱包锁；支付重放只扣一次，流水与订单同事务；并发不同订单不能透支。
- 提交先入数据库；Redis 或队列中断仍能恢复；重复消费不重复落单；真实订单、商品、地址与优惠券写入后注入 outbox 失败，验证一起回滚后可重试。
- Redis 实际执行 Lua：多 SKU 全有或全无、重复预占/释放、旧任务释放、参数不匹配拒绝，以及补偿先于延迟预占到达时的防护。
- 支付与取消竞争；渠道关单失败不先取消订单或退券；丢失支付回调通过查单恢复；确定终态停止查单；历史已取消订单收到成功支付时持久化全额原路退款。
- 退款申请提交后才调用渠道，未知结果先查询，仅明确不存在时用原退款号重发；回调落账中途失败全部回滚，重放只退一次；余额退款不增加累计充值。
- 自动退款审核和退款申请同事务保存；outbox 监听器部分执行后失败会回滚，成功处理后才标记完成。

## 全新部署

交易可靠性基础结构直接包含在原始建表迁移中；人工认款支付类型另由下文的增量迁移扩展：

- `2024_01_01_000008_create_mall_orders_table.php`：创建 `orders`、`order_submissions` 和 `trade_outbox`。提交记录与交易事件属于订单核心流程，随订单表一起创建或回滚。
- `2024_01_01_000028_create_mall_payments_table.php`：创建支付记录表及完整支付状态说明。
- `2024_01_01_000029_create_mall_payment_refunds_table.php`：创建退款记录表，默认状态为 `requested`，表示申请已持久化但尚未请求渠道。
- `2026_03_16_000001_create_after_sale_tables.php`：直接创建可容纳 `failed` 的退款状态字段。

全新环境按项目正常迁移顺序建库后，启动 HTTP、队列和定时任务进程。`FreshTradeSchemaTest` 实际执行这四个原始迁移，检查状态类型、默认值、所有表/字段备注及回滚重建；人工认款集成测试另外执行支付类型增量迁移。

`trade-recovery` 每分钟恢复提交、查询支付/退款并投递持久事件；必须保持定时任务运行。业务财务写入与事件记录同事务。事件和 webhook 按至少一次语义投递，外部接收方仍需按订单号/退款号去重，不能宣称跨服务 exactly-once。

普通订单、秒杀和团购沿用各自库存 hash key。Redis 中 `_reservation:*` 字段用于幂等，不能把它们当 SKU；不要在线清空含未完成订单的库存 hash。标记保留以防任务重放，长期归档/清理需结合订单和事件生命周期制定，当前未自动清理。

本地测试覆盖真实 MySQL/Redis 与 SDK 密码学处理；未使用生产数据库、真实支付商户、真实支付/退款请求。生产证书、回调公网连通性和渠道真实响应仍需部署环境验证。

## 后台人工认款

订单列表对未支付、未取消且未过期的订单提供“人工认款”。确认收到全部线下款项后，订单进入待发货，支付方式记为 `manual`（线下支付），同时保存独立支付记录、操作人、备注和 `order.paid` 事件。金额始终取数据库中的订单应付金额，不扣会员余额、不重复处理库存。

存在待确认支付流水时须先核实原支付渠道；本地过期不代表微信已经关单。已完成认款的请求重放不会重复写入，取消与认款共用订单行锁。线下支付不支持通过微信或余额自动退款。

已有环境上线本功能需执行枚举增量迁移及独立权限种子：

```bash
php bin/hyperf.php migrate
php bin/hyperf.php db:seed --path seeders/menu_update_20261015.php
```

随后给需要认款的角色分配 `order:order:manual-pay` 权限，并按项目部署流程重启后端、发布前端。不要在已有业务库运行无 `--path` 的全量 `db:seed`；全量菜单种子会重建菜单。存在 `manual` 订单或支付历史时，增量迁移会拒绝回滚，保留收款记录。
