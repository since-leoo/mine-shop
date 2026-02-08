<?php

declare(strict_types=1);
/**
 * This file is part of MineAdmin.
 *
 * @link     https://www.mineadmin.com
 * @document https://doc.mineadmin.com
 * @contact  root@imoi.cn
 * @license  https://github.com/mineadmin/MineAdmin/blob/master/LICENSE
 */

namespace Plugin\Since\Shipping;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        // 迁移由插件管理器自动执行 Database/Migrations
    }

    public function uninstall(): void
    {
        // 回滚由 plugin.json 的 rollback_on_uninstall 控制
    }

    public function boot(): void
    {
        // 接口绑定由 ConfigProvider dependencies 完成
    }
}
