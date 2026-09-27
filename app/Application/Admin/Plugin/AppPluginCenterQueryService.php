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

namespace App\Application\Admin\Plugin;

use App\Application\Admin\Infrastructure\AppSystemSettingCommandService;
use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;

/**
 * 插件中心查询服务。
 *
 * 插件的安装状态和基础元数据由 hyperf-plugin 负责发现，本服务只组装
 * 插件中心需要的公开信息，避免把插件目录路径暴露给管理端。
 */
final class AppPluginCenterQueryService
{
    public function __construct(
        private readonly PluginDiscovererInterface $discoverer,
        private readonly DomainSystemSettingService $settings,
        private readonly AppSystemSettingCommandService $commandService
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(): array
    {
        $plugins = [];

        foreach ($this->discoverer->discoverLocalPlugins() as $plugin) {
            $name = (string) ($plugin['name'] ?? '');
            if ($name === '') {
                continue;
            }

            $center = \is_array($plugin['center'] ?? null) ? $plugin['center'] : [];
            $config = $this->discoverer->getPluginJsonConfig($name);
            if ($center === [] && \is_array($config['center'] ?? null)) {
                $center = $config['center'];
            }

            $fallbackTitle = (string) (mb_strrchr($name, '/') ?: $name);
            $fallbackTitle = ltrim($fallbackTitle, '/');
            $plugins[] = [
                'key' => $name,
                'name' => $name,
                'title' => (string) ($center['title'] ?? $fallbackTitle),
                'description' => (string) ($plugin['description'] ?? ''),
                'version' => (string) ($plugin['version'] ?? '1.0.0'),
                'author' => (string) ($plugin['author'] ?? ''),
                'icon' => $center['icon'] ?? null,
                'sort' => (int) ($center['sort'] ?? $plugin['priority'] ?? 0),
                'installed' => (bool) ($plugin['installed'] ?? false),
                'enabled' => (bool) ($plugin['enabled'] ?? false),
                'dependencies' => \is_array($plugin['dependencies'] ?? null) ? $plugin['dependencies'] : [],
                'page' => $center['page'] ?? null,
            ];
        }

        usort($plugins, static function (array $left, array $right): int {
            $sort = $left['sort'] <=> $right['sort'];
            return $sort !== 0 ? $sort : strcmp((string) $left['key'], (string) $right['key']);
        });

        return $plugins;
    }

    /**
     * Return configuration definitions owned by a plugin. Plugins declare keys
     * in plugin.json under center.settings; this keeps the plugin center
     * independent from the legacy mall settings page.
     *
     * @return array<int, array<string, mixed>>
     */
    public function config(string $name): array
    {
        $plugin = $this->plugin($name);
        $config = $this->discoverer->getPluginJsonConfig($name);
        $center = \is_array($config['center'] ?? null) ? $config['center'] : [];
        $keys = \is_array($center['settings'] ?? null) ? $center['settings'] : [];
        $groups = config('mall.groups', []);
        $definitions = [];
        foreach ($groups as $group) {
            foreach (($group['settings'] ?? []) as $key => $definition) {
                if ($keys !== [] && ! \in_array($key, $keys, true)) {
                    continue;
                }
                if ($keys === [] && ! str_starts_with((string) $key, $name . '.')) {
                    continue;
                }
                $item = \is_array($definition) ? $definition : [];
                $value = $this->settings->get((string) $key, $item['default'] ?? null);
                if (($item['is_sensitive'] ?? false) === true) {
                    $value = (string) ($item['type'] ?? '') === 'json'
                        ? $this->maskJsonFields($value, $item['meta']['fields'] ?? [])
                        : $this->maskSensitive($value);
                }
                $definitions[] = [
                    'key' => (string) $key,
                    'label' => (string) ($item['label'] ?? $key),
                    'description' => $item['description'] ?? null,
                    'type' => (string) ($item['type'] ?? 'string'),
                    'meta' => \is_array($item['meta'] ?? null) ? $item['meta'] : [],
                    'is_sensitive' => (bool) ($item['is_sensitive'] ?? false),
                    'default' => $item['default'] ?? null,
                    'value' => $value,
                    'sort' => (int) ($item['sort'] ?? 0),
                ];
            }
        }
        usort($definitions, static fn (array $a, array $b): int => $a['sort'] <=> $b['sort']);
        return $definitions;
    }

    public function updateConfig(string $name, string $key, mixed $value): array
    {
        $definitions = $this->config($name);
        foreach ($definitions as $definition) {
            if ($definition['key'] !== $key) {
                continue;
            }
            if (($definition['is_sensitive'] ?? false) && $value === '********') {
                $value = $this->settings->get($key, $definition['default'] ?? null);
            } elseif ($definition['is_sensitive'] ?? false) {
                $value = $this->restoreMasked($value, $this->settings->get($key, $definition['default'] ?? null));
            }
            return $this->commandService->update($key, $value);
        }
        throw new \RuntimeException('配置项不属于该插件');
    }

    /** @return array<string, mixed> */
    private function plugin(string $name): array
    {
        foreach ($this->discoverer->discoverLocalPlugins() as $plugin) {
            if (($plugin['name'] ?? '') === $name) {
                return $plugin;
            }
        }
        throw new \RuntimeException('插件不存在');
    }

    private function maskSensitive(mixed $value): mixed
    {
        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->maskSensitive($item), $value);
        }
        if ($value === null || $value === '') {
            return $value;
        }
        return '********';
    }

    private function restoreMasked(mixed $value, mixed $current): mixed
    {
        if (\is_array($value) && \is_array($current)) {
            foreach ($value as $key => $item) {
                if (\array_key_exists($key, $current)) {
                    $value[$key] = $this->restoreMasked($item, $current[$key]);
                }
            }
            return $value;
        }
        return $value === '********' ? $current : $value;
    }

    private function maskJsonFields(mixed $value, mixed $fields): mixed
    {
        if (! \is_array($value) || ! \is_array($fields)) {
            return $value;
        }
        foreach ($fields as $field) {
            if (! \is_array($field)) {
                continue;
            }
            $key = (string) ($field['key'] ?? '');
            $password = ($field['input_type'] ?? '') === 'password' || ($field['is_sensitive'] ?? false) === true;
            if ($key !== '' && $password && \array_key_exists($key, $value) && $value[$key] !== '') {
                $value[$key] = '********';
            }
        }
        return $value;
    }
}
