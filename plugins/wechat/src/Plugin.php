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

        // 插件中心页面由插件自身提供，不再注册主应用菜单。
        $webSource = \dirname(__DIR__) . '/web';
        $webTarget = BASE_PATH . '/web/src/plugins/since/wechat';
        if (is_dir($webSource)) {
            $this->copyDirectory($webSource, $webTarget);
        }
    }

    public function uninstall(): void
    {
        $configFile = BASE_PATH . '/config/autoload/wechat.php';

        if (file_exists($configFile)) {
            unlink($configFile);
        }

        $webTarget = BASE_PATH . '/web/src/plugins/since/wechat';
        if (is_dir($webTarget)) {
            $this->deleteDirectory($webTarget);
        }
    }

    private function copyDirectory(string $source, string $target): void
    {
        if (! is_dir($target)) {
            mkdir($target, 0o755, true);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $targetPath = $target . \DIRECTORY_SEPARATOR . $iterator->getSubPathname();
            if ($item->isDir()) {
                if (! is_dir($targetPath)) {
                    mkdir($targetPath, 0o755, true);
                }
            } else {
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    private function deleteDirectory(string $directory): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}
