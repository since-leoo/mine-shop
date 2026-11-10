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

namespace App\Infrastructure\Plugin;

/** 插件安装/启停改变扫描范围；生产环境也必须重新生成注解与 AOP 缓存。 */
final class PluginScanCache
{
    public static function initialize(string $basePath, callable $initialize, ?string $pluginsDirectory = null): void
    {
        $basePath = rtrim($basePath, '/');
        $directory = $basePath . '/runtime/container';
        if (! is_dir($directory) && ! mkdir($directory, 0o755, true) && ! is_dir($directory)) {
            throw new \RuntimeException('无法创建插件扫描缓存目录');
        }
        $lock = fopen($directory . '/plugin-scan.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('无法打开插件扫描缓存锁');
        }
        try {
            if (! flock($lock, \LOCK_EX)) {
                throw new \RuntimeException('无法锁定插件扫描缓存');
            }
            $fingerprint = self::fingerprint($basePath, $pluginsDirectory);
            $marker = $directory . '/plugin-scan.fingerprint';
            $previous = is_file($marker) ? file_get_contents($marker) : false;
            $changed = $previous !== $fingerprint;
            if ($changed) {
                self::invalidate($directory);
            }

            // 锁覆盖 Hyperf 的扫描子进程；失败时不记录新指纹，下次启动仍会重建。
            $initialize();
            if (self::fingerprint($basePath, $pluginsDirectory) !== $fingerprint) {
                // 安装/启停操作本身不持此启动锁，不能把变更中的扫描结果标记为有效。
                if (is_file($marker) && ! unlink($marker)) {
                    throw new \RuntimeException('无法撤销插件扫描指纹');
                }
                throw new \RuntimeException('插件状态在启动扫描期间发生变化，请重新启动');
            }
            if ($changed && file_put_contents($marker, $fingerprint) === false) {
                throw new \RuntimeException('无法保存插件扫描指纹');
            }
        } finally {
            flock($lock, \LOCK_UN);
            fclose($lock);
        }
    }

    private static function fingerprint(string $basePath, ?string $pluginsDirectory): string
    {
        if ($pluginsDirectory === null) {
            $file = $basePath . '/config/autoload/plugins.php';
            $config = is_file($file) ? require $file : [];
            $pluginsDirectory = \is_array($config) && ! empty($config['plugins_path']) ? $config['plugins_path'] : 'plugins';
        }
        $pluginsPath = $basePath . '/' . $pluginsDirectory;
        $plugins = [];
        $names = is_dir($pluginsPath) ? scandir($pluginsPath) : [];
        if ($names === false) {
            throw new \RuntimeException('无法读取插件目录');
        }
        foreach ($names as $name) {
            if ($name === '.' || $name === '..') {
                continue;
            }
            $path = $pluginsPath . '/' . $name;
            $manifest = $path . '/plugin.json';
            if (! is_file($manifest)) {
                continue;
            }
            $raw = file_get_contents($manifest);
            if ($raw === false) {
                throw new \RuntimeException('无法读取插件清单: ' . $name);
            }
            $definition = json_decode($raw, true);
            // 与 PluginBootstrap 的生效条件一致，未安装或已停用插件不参与扫描。
            if (empty($definition['namespace']) || empty($definition['enabled']) || ! file_exists($path . '/install.lock')) {
                continue;
            }
            $installation = hash_file('sha256', $path . '/install.lock');
            if ($installation === false) {
                throw new \RuntimeException('无法读取插件安装标记: ' . $name);
            }
            $plugins[$name] = ['manifest' => hash('sha256', $raw), 'installation' => $installation];
        }
        ksort($plugins);
        return hash('sha256', json_encode(['version' => 1, 'directory' => $pluginsDirectory, 'plugins' => $plugins], \JSON_THROW_ON_ERROR));
    }

    private static function invalidate(string $directory): void
    {
        // 仅删除框架生成文件，绝不递归删除 runtime 或插件安装目录。
        $files = [$directory . '/scan.cache', $directory . '/classes.cache', $directory . '/aspects.cache'];
        array_push($files, ...(glob($directory . '/proxy/*.proxy.php') ?: []));
        foreach ($files as $file) {
            if ((file_exists($file) || is_link($file)) && ! unlink($file)) {
                throw new \RuntimeException('无法刷新插件扫描缓存: ' . basename($file));
            }
        }
    }
}
