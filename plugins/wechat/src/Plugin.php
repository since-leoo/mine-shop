<?php

declare(strict_types=1);

namespace Plugin\Wechat;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        $source = dirname(__DIR__) . '/publish/wechat.php';
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
