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

namespace Plugin\ExportCenter;

use SinceLeoo\Plugin\Contract\AbstractPlugin;

class Plugin extends AbstractPlugin
{
    public function install(): void
    {
        try {
            $publishDir = \dirname(__DIR__) . '/publish';

            // 发布配置文件
            $configSource = $publishDir . '/export.php';
            $configDest = BASE_PATH . '/config/autoload/export.php';
            if (! file_exists($configDest) && file_exists($configSource)) {
                copy($configSource, $configDest);
            }

            // 复制前端文件到插件目录
            $webSource = \dirname(__DIR__) . '/web';
            $webTarget = BASE_PATH . '/web/src/plugins/since/export-center';

            if (is_dir($webSource)) {
                $this->copyDirectory($webSource, $webTarget);
            }
        } catch (\Exception $exception) {
            var_dump($exception->getMessage(), $exception->getFile());
        }
    }

    public function uninstall(): void
    {
        // 清理发布的配置文件
        $configFile = BASE_PATH . '/config/autoload/export.php';
        if (file_exists($configFile)) {
            unlink($configFile);
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
}
