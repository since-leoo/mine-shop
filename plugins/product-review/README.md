# 商品评价插件

从主应用迁出的现有功能，保留数据库表名、权限码与接口：`/admin/review/*`、`/api/v1/review/*`。

异常中文文案与业务码集中维护在 `app/Infrastructure/Error/ProductReviewError.php`（`420xxx`），覆盖重复评价、评分、内容、审核状态与评价不存在；保留原有 `DomainException` / `RuntimeException` 类型和状态转换约束。

## 目录与边界

- `src/`：插件自己的领域、应用、后台接口和持久化实现。
- `web/`：后台唯一实现源码；`web/src/plugins/since/product-review/index.ts` 和旧后台路径仅作兼容适配，按服务端 installed/enabled 状态加载。
- `Database/Migrations/`：唯一建表来源，保留迁出前的文件名与时间顺序，直接建立当前结构。字段变更已归并原建表文件；不包含历史数据接管或补数据逻辑。
- 核心 `App\Domain\Infrastructure\Plugin\Contract` 定义能力端口；`OptionalCapabilityFactory` 读取启用插件 Provider 的 `plugin_capabilities`，未加载时选择核心降级实现。
- 本插件没有对另外两个新插件的硬依赖。经营分析通过 `ReviewStatisticsInterface` 获取可选评价统计；装修跨域读取经 `DiyCatalogDataSourceInterface`；评价资格经 `ReviewEligibilityInterface`。

## 全新部署与停用

仓库附带的 `install.lock` 只标记随包安装状态，不代表当前数据库已有表。标准迁移会发现所有已安装插件（包括暂时禁用的插件），由数据库 `migrations` 表记录实际完成情况：

```sh
php bin/hyperf.php migrate --force
php bin/hyperf.php db:seed --force
```

本分支的迁移按全新部署整理：只有插件内一份建表源码，没有根目录副本或 `adopt` 包装。不将这些改写后的初始迁移当作旧数据库升级脚本；本轮没有操作业务数据库。

新加入的未安装插件仍使用 `plugin:install`，安装器与标准迁移共用相同目录发现规则，兼容 `Database/Migrations` 与 `migrations`。正常部署无需为了建表执行 `plugin:install --force`。卸载默认不回滚表（`rollback_on_uninstall=false`）；显式执行数据库回滚会执行 `down()` 删除相应表。

设置 `plugin.json` 的 `enabled=false` 后按既有部署方式清理容器缓存并重启 HTTP/任务进程。它是启动期插件开关，前端本地开关不会代替后端停用。

停用行为：前台查询返回空结果及 enabled=false，订单不展示去评价按钮，提交返回明确未启用；历史评价保留。

重新启用后重启进程即可继续读取历史记录。删除业务数据是独立运维动作，卸载不包含该操作。

## 验证

```sh
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/PluginExtraction
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.<id>/mysql.sock php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/PluginExtraction
```

集成测试只接受独立测试服务器 socket，为每个测试创建并销毁随机数据库；覆盖全新建表、所有表字段备注、真实业务查询及停用降级。不读取 `.env`，不连接业务库。

小程序评价查询适配位于 `miniprogram/src/plugins/product-review/services`，既有服务路径仅重导出；公开提交 Request/DTO 与 ReviewInput 契约保留核心，禁用插件时仍能给出明确响应。
