<?php

namespace App\Infrastructure\Interface;

/**
 * 缓存接口定义
 * 定义了缓存操作的基本方法，包括键值对存储、哈希表操作和缓存清理等功能
 */
interface InterfaceCache
{
    /**
     * 设置缓存键前缀
     *
     * @param string $prefix 缓存键前缀
     * @return InterfaceCache
     */
    public function setPrefix(string $prefix): self;

    /**
     * 获取缓存值
     *
     * @param string $key 缓存键
     * @return mixed 缓存值，如果不存在则返回null或其他默认值
     */
    public function get(string $key): mixed;

    /**
     * 设置缓存值
     *
     * @param string $key 缓存键
     * @param mixed $value 缓存值
     * @param int $ttl 过期时间（秒），0表示永不过期
     * @return bool 操作是否成功
     */
    public function set(string $key, mixed $value, int $ttl = 0): bool;

    /**
     * 删除指定缓存
     *
     * @param string ...$key 要删除的缓存键
     * @return bool 操作是否成功
     */
    public function delete(string ...$key): bool;

    /**
     * 清空缓存
     *
     * @param string $prefix 要清空的缓存前缀，如果为空则清空所有缓存
     * @return bool 操作是否成功
     */
    public function clear(string $prefix = ''): bool;

    /**
     * 设置哈希表字段值
     *
     * @param string $key 哈希表键
     * @param string $field 字段名
     * @param mixed $value 字段值
     * @return bool 操作是否成功
     */
    public function hSet(string $key, string $field, mixed $value): bool;

    /**
     * 获取哈希表字段值
     *
     * @param string $key 哈希表键
     * @param string $field 字段名
     * @return mixed 字段值，如果不存在则返回null或其他默认值
     */
    public function hGet(string $key, string $field): mixed;

    /**
     * 批量设置哈希表字段值
     *
     * @param string $key 哈希表键
     * @param array $fields 字段名和值组成的数组
     * @return bool 操作是否成功
     */
    public function hMset(string $key, array $fields): bool;

    /**
     * 批量获取哈希表字段值
     *
     * @param string $key 哈希表键
     * @param array $fields 要获取的字段名组成的数组
     * @return array 字段名和值组成的数组
     */
    public function hMget(string $key, array $fields): array;

    /**
     * 删除哈希表字段
     *
     * @param string $key 哈希表键
     * @param string $field 要删除的字段名
     * @return bool 操作是否成功
     */
    public function hDel(string $key, string $field): bool;
}
