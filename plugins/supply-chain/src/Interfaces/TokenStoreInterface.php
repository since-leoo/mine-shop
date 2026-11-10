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

namespace Plugin\SupplyChain\Interfaces;

interface TokenStoreInterface
{
    public function get(string $key): ?string;

    public function put(string $key, string $token, int $ttl): void;

    public function forgetIfEquals(string $key, string $token): void;

    public function lock(string $key, string $owner, int $ttl): bool;

    public function unlock(string $key, string $owner): void;
}
