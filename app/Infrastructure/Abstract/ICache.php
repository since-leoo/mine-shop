<?php

namespace App\Infrastructure\Abstract;

use App\Infrastructure\Interface\InterfaceCache;
use Hyperf\Redis\RedisFactory;
use Hyperf\Redis\RedisProxy;

/**
 * @method mixed mGet(array $fields)
 */
class ICache implements InterfaceCache
{
    private string $prefix = '';

    public string $poolName = 'default';

    private RedisProxy $redis;

    public function __construct(private readonly RedisFactory $redisFactory) {
        $this->prefix = config('cache.default.prefix');
        $this->redis = $this->redisFactory->get($this->poolName);
    }

    /**
     * @param string $prefix
     * @return ICache
     */
    public function setPrefix(string $prefix): self
    {
        $this->prefix .= ':' . $prefix;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function get(string $key): mixed
    {
        return $this->redis->get($this->prefix . ':' . $key);
    }

    /**
     * @param string $key
     * @param mixed $value
     * @param int $ttl
     * @return bool
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool
    {
        return (bool) $this->redis->set($this->prefix . ':' . $key, $value, $ttl);
    }

    /**
     * @param string ...$key
     * @return bool
     */
    public function delete(string ...$key): bool
    {
        $prefixedKeys = [];
        foreach ($key as $k) {
            $prefixedKeys[] = $this->prefix . ':' . $k;
        }
        return (bool) $this->redis->del(...$prefixedKeys);
    }

    /**
     * @param string $prefix
     * @return bool
     */
    public function clear(string $prefix = ''): bool
    {
        return (bool) $this->redis->del($this->prefix . ':' . $prefix);
    }

    /**
     * @param string $key
     * @param string $field
     * @param mixed $value
     * @return bool
     */
    public function hSet(string $key, string $field, mixed $value): bool
    {
        return (bool) $this->redis->hSet($this->prefix . ':' . $key , $field, $value);
    }

    /**
     * @param string $key
     * @param string $field
     * @return mixed
     */
    public function hGet(string $key, string $field): mixed
    {
        return $this->redis->hGet($this->prefix . ':' . $key, $field);
    }

    /**
     * @param string $key
     * @return array
     */
    public function hGetAll(string $key): array
    {
        return $this->redis->hGetAll($this->prefix . ':' . $key);
    }

    /**
     * @param string $key
     * @param array $fields
     * @return bool
     */
    public function hMset(string $key, array $fields): bool
    {
        return (bool) $this->redis->hMset($this->prefix . ':' . $key, $fields);
    }

    /**
     * @param string $key
     * @param array $fields
     * @return array
     */
    public function hMget(string $key, array $fields): array
    {
        return $this->redis->hMget($this->prefix . ':' . $key, $fields);
    }

    /**
     * @param string $key
     * @param string $field
     * @return bool
     */
    public function hDel(string $key, string $field): bool
    {
        return (bool) $this->redis->hDel($this->prefix . ':' . $key, $field);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return $this->redis->$name(...$arguments);
    }
}