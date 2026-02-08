<?php

declare(strict_types=1);

namespace Plugin\Since\Geo;

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
}
