# 店铺装修插件

从主应用迁出的现有功能，保留数据库表名、权限码与接口：`/admin/diy/*`、`/api/v1/diy/pages/{pageKey}`、`/api/v1/diy/preview/{token}`。

## 目录与边界

- `src/`：插件自己的领域、应用、后台接口和持久化实现。
- `web/`：后台唯一实现源码；`web/src/plugins/since/diy/index.ts` 和旧后台路径仅作兼容适配，按服务端 installed/enabled 状态加载。
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

停用行为：前台查询返回 page=null、components=[]，原基础首页/分类/会员页继续展示；管理入口提示未启用，发布记录和版本保留。

重新启用后重启进程即可继续读取历史记录。删除业务数据是独立运维动作，卸载不包含该操作。

## 定时发布

启用插件并重启任务进程后，`diy-scheduled-publish` 每分钟处理最多 100 条到期预约。沿用普通发布语义：切换已发布版本，不自动启用处于停用状态的页面。

预约只能选择当前页面通过校验的草稿，同一页面同时只允许一个待执行预约。预约后的版本保持不变，继续保存/重置页面会另建草稿；取消预约需要匹配页面及记录。执行时重新校验操作者是否存在、是否停用、是否仍有发布权限，以及页面是否仍在其当前数据范围内。

发布记录与页面版本在同一事务内更新，重复调度只生效一次。校验/权限或发布失败会记录 `failed` 和可展示原因；内部异常详情只写服务日志。修复原因后重新预约，失败记录不会循环重试。进程在提交前退出则事务回滚，下次扫描继续处理仍为 `pending` 的记录。

无需新增数据库字段。新增定时任务依赖 Hyperf 定时任务进程正常运行；停用插件期间不执行，重新启用后会继续检查过期预约及最新权限。

## 验证

```sh
php vendor/bin/phpunit -c phpunit.unit.xml tests/Unit/PluginExtraction
MINE_SHOP_TEST_MYSQL_SOCKET=/tmp/mine-shop-tests.<id>/mysql.sock php vendor/bin/phpunit -c phpunit.integration.xml tests/Integration/PluginExtraction
```

集成测试只接受独立测试服务器 socket，为每个测试创建并销毁随机数据库；覆盖全新建表、所有表字段备注、真实业务查询及停用降级。不读取 `.env`，不连接业务库。

小程序/H5 的唯一实现位于 `miniprogram/src/plugins/diy`，旧 `components/diy*` 和 `services/diy` 是薄导出适配，避免破坏 Taro 固定 sourceRoot。默认种子移到插件内，首次安装可创建默认模板/页面，重装不覆盖商家已有页面和模板。

根目录 `db:seed` 只初始化核心基础数据。需要默认装修模板和页面时，再执行 `php bin/hyperf.php db:seed --path plugins/diy/seeders --force`。种子只提供初始内容，不覆盖已有编辑；无需为此强制重装插件。
