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

namespace App\Application\Query;

use App\Domain\Permission\Service\DomainUserService;
use App\Infrastructure\Model\Permission\User;
use Hyperf\Collection\Collection;
use Psr\SimpleCache\CacheInterface;

final class AppUserQueryService
{
    public function __construct(
        private readonly DomainUserService $userService,
        private readonly CacheInterface $cache
    ) {}

    /**
     * 分页查询用户.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function page(array $filters, int $page, int $pageSize): array
    {
        return $this->userService->page($filters, $page, $pageSize);
    }

    public function find(int $id): ?User
    {
        return $this->userService->findById($id);
    }

    public function findCached(int $id, int $ttl = 60): ?User
    {
        $key = (string) $id;
        if ($this->cache->has($key)) {
            $cached = $this->cache->get($key);
            return $cached instanceof User ? $cached : null;
        }
        $user = $this->userService->findById($id);
        $this->cache->set($key, $user, $ttl);
        return $user;
    }

    public function getRoles(int $userId): Collection
    {
        $user = $this->userService->findById($userId);
        if (! $user) {
            return new Collection();
        }
        return $user->roles()->get();
    }
}
