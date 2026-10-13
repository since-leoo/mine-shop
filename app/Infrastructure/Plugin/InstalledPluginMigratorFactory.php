<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Database\Migrations\MigrationRepositoryInterface;
use Hyperf\Database\Migrations\Migrator;
use Hyperf\Support\Filesystem\Filesystem;
use Psr\Container\ContainerInterface;
use SinceLeoo\Plugin\Contract\PluginConfigReaderInterface;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;

/** 将已安装插件的建表文件注册到标准迁移命令，避免只迁移核心目录造成漏表。 */
final class InstalledPluginMigratorFactory
{
    public function __invoke(ContainerInterface $container): Migrator
    {
        $migrator = new Migrator(
            $container->get(MigrationRepositoryInterface::class),
            $container->get(ConnectionResolverInterface::class),
            $container->get(Filesystem::class),
        );
        $reader = $container->get(PluginConfigReaderInterface::class);
        $paths = [];
        foreach ($container->get(PluginDiscovererInterface::class)->getInstalledPlugins() as $plugin) {
            // 停用只影响业务能力，已安装插件仍需维护表结构，避免重新启用时缺字段。
            // install.lock 表示代码已安装；迁移是否执行由数据库的 migrations 表判断。
            $path = $reader->getMigrationPath($plugin['path']);
            if ($path !== null && ($path = realpath($path)) !== false) {
                $paths[$path] = $path;
            }
        }
        foreach ($paths as $path) {
            $migrator->path($path);
        }
        // 框架会合并核心目录并按原始迁移文件名排序；显式 --path 仍只执行指定目录。
        return $migrator;
    }
}
