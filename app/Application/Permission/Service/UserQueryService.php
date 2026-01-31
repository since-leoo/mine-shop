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

namespace App\Application\Permission\Service;

use App\Domain\Permission\Repository\UserRepository;
use App\Domain\Shared\ValueObject\PageQuery;
use App\Infrastructure\Model\Permission\User;
use Hyperf\Collection\Collection;
use Psr\SimpleCache\CacheInterface;

final class UserQueryService
{
    public function __construct(
        private readonly UserRepository $repository,
        private readonly CacheInterface $cache
    ) {}

    public function paginate(PageQuery $query): array
    {
        return $this->repository->page(
            $query->getFilters(),
            $query->getPage(),
            $query->getPageSize()
        );
    }

    public function find(int $id): ?User
    {
        return $this->repository->findById($id);
    }

    public function findCached(int $id, int $ttl = 60): ?User
    {
        $key = (string) $id;
        if ($this->cache->has($key)) {
            /** @var null|User $cached */
            return $this->cache->get($key);
        }
        $user = $this->repository->findById($id);
        $this->cache->set($key, $user, $ttl);
        return $user;
    }

    public function getRoles(int $userId): Collection
    {
        $user = $this->repository->findById($userId);
        if (! $user) {
            return new Collection();
        }
        return $user->roles()->get();
    }
}
