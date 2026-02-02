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

use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Service\UserService;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

final class UserCommandService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly CacheInterface $cache
    ) {}

    public function create(UserEntity $entity): User
    {
        $user = Db::transaction(fn () => $this->userService->create($entity));
        $this->forgetCache((int) $user->id);
        return $user;
    }

    public function update(UserEntity $entity): ?User
    {
        $user = Db::transaction(fn () => $this->userService->update($entity));
        if ($user) {
            $this->forgetCache((int) $user->id);
        }
        return $user;
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        $deleted = $this->userService->delete($ids);
        foreach ($ids as $id) {
            $this->forgetCache((int) $id);
        }
        return $deleted;
    }

    public function resetPassword(int $id): bool
    {
        $result = $this->userService->resetPassword($id);
        $result && $this->forgetCache($id);
        return $result;
    }

    /**
     * @param string[] $roleCodes
     */
    public function grantRoles(int $userId, array $roleCodes): void
    {
        $this->userService->grantRoles($userId, $roleCodes);
        $this->forgetCache($userId);
    }

    private function forgetCache(int $id): void
    {
        $this->cache->delete((string) $id);
    }
}
