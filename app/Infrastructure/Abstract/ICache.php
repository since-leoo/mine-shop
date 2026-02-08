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

namespace App\Infrastructure\Abstract;

use App\Infrastructure\Interface\InterfaceCache;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

class ICache implements InterfaceCache
{
    public string $poolName = 'default';

    private string $prefix = '';

    private string $defaultPrefix = '';

    private RedisProxy $redis;

    public function __construct(private readonly RedisFactory $redisFactory)
    {
        $this->redis = $this->redisFactory->get($this->poolName);
        $this->defaultPrefix = config('cache.default.prefix');
    }

    /**
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->redis->{$name}(...$arguments);
    }

    public function setPrefix(string $prefix): self
    {
        $this->prefix = $this->defaultPrefix . $prefix;
        return $this;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function get(string $key): mixed
    {
        return $this->redis->get($this->prefix . ':' . $key);
    }

    public function set(string $key, mixed $value, mixed $options = []): bool
    {
        $cacheKey = $this->prefix . ':' . $key;

        return (bool) $this->redis->set($cacheKey, $value, $options);
    }

    public function delete(string ...$key): bool
    {
        $prefixedKeys = [];
        foreach ($key as $k) {
            $prefixedKeys[] = $this->prefix . ':' . $k;
        }
        return (bool) $this->redis->del(...$prefixedKeys);
    }

    /**
     * @param string[] $keys
     * @return array<int, mixed>
     */
    public function mGet(array $keys): array
    {
        $prefixedKeys = array_map(fn (string $key) => $this->prefix . ':' . $key, $keys);
        /** @var array<int, mixed>|false $result */
        $result = $this->redis->mGet($prefixedKeys);
        return \is_array($result) ? $result : [];
    }

    public function clear(string $prefix = ''): bool
    {
        return (bool) $this->redis->del($this->prefix . ':' . $prefix);
    }

    public function hSet(string $key, string $field, mixed $value): bool
    {
        return (bool) $this->redis->hSet($this->prefix . ':' . $key, $field, $value);
    }

    public function hGet(string $key, string $field): mixed
    {
        return $this->redis->hGet($this->prefix . ':' . $key, $field);
    }

    public function hGetAll(string $key): array
    {
        return $this->redis->hGetAll($this->prefix . ':' . $key);
    }

    public function hMset(string $key, array $fields): bool
    {
        return (bool) $this->redis->hMset($this->prefix . ':' . $key, $fields);
    }

    public function hMget(string $key, array $fields): array
    {
        return $this->redis->hMget($this->prefix . ':' . $key, $fields);
    }

    public function hDel(string $key, string $field): bool
    {
        return (bool) $this->redis->hDel($this->prefix . ':' . $key, $field);
    }

    public function eval(string $script, array $args = [], int $numKeys = 0): bool
    {
        return (bool) $this->redis->eval($script, $args, $numKeys);
    }

    public function hIncrBy(string $key, string $field, int $value): false|int|\Redis
    {
        return $this->redis->hIncrBy($key, $field, $value);
    }

    public function expire(string $key, int $int): bool|\Redis
    {
        return $this->redis->expire($this->prefix . ':' . $key, $int);
    }
}
