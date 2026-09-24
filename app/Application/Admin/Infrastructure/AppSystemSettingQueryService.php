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

namespace App\Application\Admin\Infrastructure;

use App\Domain\Infrastructure\SystemSetting\Service\DomainSystemSettingService;
use SinceLeoo\Plugin\Contract\PluginDiscovererInterface;

final class AppSystemSettingQueryService
{
    public function __construct(
        private readonly DomainSystemSettingService $service,
        private readonly PluginDiscovererInterface $pluginDiscoverer
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->service->get($key, $default);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function group(string $group): array
    {
        $owned = $this->pluginSettingKeys();
        return array_values(array_filter(
            $this->service->groupDetails($group),
            static fn (array $item): bool => ! isset($owned[$item['key']])
        ));
    }

    /**
     * @return array<int, array{key:string,label:string,description:?string}>
     */
    public function groups(): array
    {
        $owned = $this->pluginSettingKeys();
        $groups = [];
        foreach ($this->service->groups() as $group) {
            $items = $this->service->groupDetails((string) $group['key']);
            $visible = array_filter($items, static fn (array $item): bool => ! isset($owned[$item['key']]));
            if ($visible !== []) {
                $groups[] = $group;
            }
        }
        return $groups;
    }

    /**
     * 获取多个配置的值.
     *
     * @param string[] $keys 配置键数组
     * @return array<string, mixed>
     */
    public function getValues(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->service->get($key);
        }
        return $result;
    }

    /** @return array<string, true> */
    private function pluginSettingKeys(): array
    {
        $keys = [];
        foreach ($this->pluginDiscoverer->discoverLocalPlugins() as $plugin) {
            $name = (string) ($plugin['name'] ?? '');
            if ($name === '') {
                continue;
            }
            $center = $this->pluginDiscoverer->getPluginJsonConfig($name)['center'] ?? [];
            foreach (($center['settings'] ?? []) as $key) {
                if (\is_string($key) && $key !== '') {
                    $keys[$key] = true;
                }
            }
        }
        return $keys;
    }
}
