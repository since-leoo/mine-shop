# 秒杀会场查询回归

`SeckillPromotionQueryTest` 使用原始建表迁移与独立随机 MySQL 数据库，覆盖真实 `Request::toDto → Controller → Application → Domain → Repository → Transformer` 链路；只替换 HTTP 参数读取和无关系统设置/商品详情依赖。验证指定活动与场次、默认活动、归属不一致、未来/结束场预览、禁用内容、库存元数据与数量限制。

整个隔离测试套件可运行：

```sh
bash tests/run-isolated.sh
```

已有私有测试服务时可单独运行（socket 必须为 `/tmp/mine-shop-tests.*/mysql.sock`）：

```sh
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.XXXXXX/mysql.sock php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/Promotion
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/Promotion
```

测试从不读取应用 `.env`，每个用例创建并销毁自己的随机数据库，不发真实支付请求。
