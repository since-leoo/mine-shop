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

namespace App\Domain\SystemSetting\Service;

use App\Domain\SystemSetting\Entity\SystemSettingEntity;
use App\Domain\SystemSetting\Repository\SystemSettingRepository;
use App\Infrastructure\Abstract\ICache;
use App\Infrastructure\Abstract\IService;
use Hamcrest\Description;
use Hyperf\Codec\Json;

/**
 * 系统设置服务类
 * 提供系统配置的获取、更新、缓存管理等功能.
 */
final class SystemSettingService extends IService
{
    private const CACHE_PREFIX = 'system:settings';

    /**
     * 构造函数.
     *
     * @param SystemSettingRepository $repository 配置仓库
     * @param ICache $cache 缓存接口
     */
    public function __construct(
        public readonly SystemSettingRepository $repository,
        private readonly ICache $cache
    ) {
        $this->cache->setPrefix(self::CACHE_PREFIX);
    }

    /**
     * 根据键名获取配置值
     *
     * @param string $key 配置键名
     * @param mixed $default 默认值
     * @return mixed 配置值
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $setting = $this->rememberSetting($key);

        return $setting['value'] ?? $default;
    }

    /**
     * 获取指定分组的所有配置项.
     *
     * @param string $group 分组名称
     * @return array<int, array<string, mixed>> 配置项数组
     */
    public function getGroup(string $group): array
    {
        $cacheKey = $this->groupCacheKey($group);
        $cached = $this->cache->get($cacheKey);
        if (! empty($cached)) {
            return Json::decode($cached, true);
        }

        // 从数据库查询并转换为响应格式
        $settings = array_map(
            static fn (SystemSettingEntity $entity) => $entity->toResponse(),
            $this->repository->findByGroup($group)
        );

        $settingsCache = Json::encode($settings, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR);

        $this->cache->set($cacheKey, $settingsCache, 3600);

        return $settings;
    }

    /**
     * 获取可用的配置分组信息.
     *
     * @return array<int, array{key:string,label:string,description:?string}>
     */
    public function groups(): array
    {
        $groups = [];
        foreach ($this->configGroups() as $key => $group) {
            $groups[] = [
                'key' => (string) $key,
                'label' => (string) ($group['label'] ?? $key),
                'description' => $group['description'] ?? null,
            ];
        }

        return $groups;
    }

    /**
     * 获取分组配置详情（包含定义与当前值）.
     *
     * @param string $group 分组名称
     * @return array<int, array<string, mixed>> 配置详情数组
     */
    public function groupDetails(string $group): array
    {
        $definition = $this->configGroups()[$group] ?? null;
        if (! $definition) {
            throw new \RuntimeException(\sprintf('配置分组 %s 不存在', $group));
        }

        $items = $definition['settings'] ?? [];
        $values = $this->indexByKey($this->getGroup($group));

        $result = [];
        foreach ($items as $key => $item) {
            $meta = $item['meta'] ?? [];
            $isSensitive = (bool) ($item['is_sensitive'] ?? false);
            $value = $values[$key]['value'] ?? ($item['default'] ?? null);

            $result[] = [
                'key' => (string) $key,
                'label' => (string) ($item['label'] ?? $key),
                'type' => (string) ($item['type'] ?? 'string'),
                'description' => $item['description'] ?? null,
                'meta' => \is_array($meta) ? $meta : [],
                'is_sensitive' => $isSensitive,
                'default' => $item['default'] ?? null,
                'value' => $value,
            ];
        }

        return $result;
    }

    /**
     * 更新系统配置.
     *
     * @param SystemSettingEntity $command 配置实体
     * @return array 更新后的配置响应数据
     */
    public function update(SystemSettingEntity $command): array
    {
        $entity = $this->repository->findEntityByKey($command->getKey());
        if (! $entity) {
            [$group, $definition] = $this->definitionByKey($command->getKey());
            if (! $definition) {
                throw new \RuntimeException(\sprintf('系统配置 %s 不存在', $command->getKey()));
            }

            $entity = SystemSettingEntity::fromDefinition($group, $command->getKey(), $definition);
        }

        $saved = $this->repository->saveEntity($entity->withValue($command->getValue()));

        $this->flushCache($saved->getGroup(), $saved->getKey());

        return $saved->toResponse();
    }

    /**
     * 清除指定分组和键名的缓存.
     *
     * @param string $group 分组名称
     * @param string $key 配置键名
     */
    public function flushCache(string $group, string $key): void
    {
        $this->cache->setPrefix(self::CACHE_PREFIX);
        $this->cache->delete($this->groupCacheKey($group));
        $this->cache->delete($this->keyCacheKey($key));
    }

    /**
     * 获取配置分组定义.
     *
     * @return array<string, mixed> 配置分组数组
     */
    private function configGroups(): array
    {
        return config('mall.groups', []);
    }

    /**
     * 根据配置键名查找对应的分组和定义.
     *
     * @param string $key 配置键名
     * @return array 包含分组名和定义的数组
     */
    private function definitionByKey(string $key): array
    {
        foreach ($this->configGroups() as $groupKey => $group) {
            $settings = $group['settings'] ?? [];
            if (isset($settings[$key])) {
                return [$groupKey, $settings[$key]];
            }
        }

        return ['', null];
    }

    /**
     * 将配置项数组按键名索引.
     *
     * @param array<int, array<string, mixed>> $items 配置项数组
     * @return array<string, array<string, mixed>> 按键名索引的配置项数组
     */
    private function indexByKey(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[$item['key']] = $item;
        }
        return $result;
    }

    /**
     * 从缓存或数据库中获取并记住配置项.
     *
     * @param string $key 配置键名
     * @return array 配置项数据
     */
    private function rememberSetting(string $key): array
    {
        $cacheKey = $this->keyCacheKey($key);
        $cached = $this->cache->get($cacheKey);
        if (! empty($cached)) {
            return Json::decode($cached, true);
        }

        $entity = $this->repository->findEntityByKey($key);
        if (! $entity) {
            return [];
        }

        $payload = $entity->toResponse();
        $this->cache->set($cacheKey, Json::encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR), 3600);

        return $payload;
    }

    /**
     * 生成配置键名的缓存键.
     *
     * @param string $key 配置键名
     * @return string 缓存键
     */
    private function keyCacheKey(string $key): string
    {
        return \sprintf('key:%s', $key);
    }

    /**
     * 生成配置分组的缓存键.
     *
     * @param string $group 分组名称
     * @return string 缓存键
     */
    private function groupCacheKey(string $group): string
    {
        return \sprintf('group:%s', $group);
    }
}
