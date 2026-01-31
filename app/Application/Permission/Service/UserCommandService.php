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
use App\Domain\Permission\Repository\RoleRepository;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

final class UserCommandService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository,
        private readonly CacheInterface $cache
    ) {}

    public function create(UserEntity $entity): User
    {
        return Db::transaction(function () use ($entity) {
            /** @var User $user */
            $user = $this->userRepository->getModel()->newQuery()->create($entity->toArray());
            $this->syncRelations($user, $entity);
            $this->forgetCache((int) $user->id);
            return $user;
        });
    }

    public function update(UserEntity $entity): ?User
    {
        return Db::transaction(function () use ($entity) {
            /** @var null|User $user */
            $user = $this->userRepository->findById($entity->getId());
            if (! $user) {
                return null;
            }
            $payload = $entity->toArray();
            $payload !== [] && $user->fill($payload)->save();
            $this->syncRelations($user, $entity);
            $this->forgetCache((int) $user->id);
            return $user;
        });
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        $deleted = $this->userRepository->deleteByIds($ids);
        foreach ($ids as $id) {
            $this->forgetCache((int) $id);
        }
        return $deleted;
    }

    public function resetPassword(int $id): bool
    {
        /** @var null|User $user */
        $user = $this->userRepository->findById($id);
        if (! $user) {
            return false;
        }
        $user->resetPassword();
        $user->save();
        $this->forgetCache($id);
        return true;
    }

    /**
     * @param string[] $roleCodes
     */
    public function grantRoles(int $userId, array $roleCodes): void
    {
        /** @var null|User $user */
        $user = $this->userRepository->findById($userId);
        if (! $user) {
            return;
        }
        $roleIds = $this->roleRepository->listByCodes($roleCodes)->pluck('id')->toArray();
        $user->roles()->sync($roleIds);
        $this->forgetCache($userId);
    }

    private function syncRelations(User $user, UserEntity $entity): void
    {
        if ($entity->shouldSyncDepartments()) {
            $user->department()->sync($entity->getDepartmentIds());
        }
        if ($entity->shouldSyncPositions()) {
            $user->position()->sync($entity->getPositionIds());
        }
        if ($entity->shouldSyncPolicy()) {
            $policy = $user->policy()->first();
            $policyPayload = $entity->getPolicy()?->toArray() ?? [];
            if ($policyPayload === []) {
                $policy?->delete();
            } else {
                unset($policyPayload['id']);
                if ($policy) {
                    $policy->fill($policyPayload)->save();
                } else {
                    $user->policy()->create($policyPayload);
                }
            }
        }
    }

    private function forgetCache(int $id): void
    {
        $this->cache->delete((string) $id);
    }
}
