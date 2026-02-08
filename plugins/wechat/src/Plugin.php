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

namespace Plugin\Wechat;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        $source = \dirname(__DIR__) . '/publish/wechat.php';
        $dest = BASE_PATH . '/config/autoload/wechat.php';

        if (! file_exists($dest) && file_exists($source)) {
            copy($source, $dest);
        }
    }

    public function uninstall(): void
    {
        $configFile = BASE_PATH . '/config/autoload/wechat.php';

        if (file_exists($configFile)) {
            unlink($configFile);
        }
    }
}
