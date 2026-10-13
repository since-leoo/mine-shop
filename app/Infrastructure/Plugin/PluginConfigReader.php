<?php

declare(strict_types=1);

namespace App\Infrastructure\Plugin;

/** 同时兼容框架的 Database 目录约定与本项目的小写迁移、种子目录。 */
final class PluginConfigReader extends \SinceLeoo\Plugin\PluginConfigReader
{
    public function getMigrationPath(string $pluginPath): ?string
    {
        return parent::getMigrationPath($pluginPath) ?? $this->directory($pluginPath, 'Database/Migrations')
            ?? $this->directory($pluginPath, 'migrations');
    }

    public function getSeederPath(string $pluginPath): ?string
    {
        return parent::getSeederPath($pluginPath) ?? $this->directory($pluginPath, 'Database/Seeders')
            ?? $this->directory($pluginPath, 'seeders');
    }

    private function directory(string $pluginPath, string $directory): ?string
    {
        $path = rtrim($pluginPath, '/') . '/' . $directory;
        return is_dir($path) ? $path : null;
    }
}
