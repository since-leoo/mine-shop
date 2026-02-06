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

namespace App\Domain\Permission\Service;

use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Repository\RoleRepository;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\User;

final class UserService extends IService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoleRepository $roleRepository
    ) {}

    public function create(UserEntity $entity): User
    {
        /** @var User $user */
        $user = $this->userRepository->create($entity->toArray());
        $this->syncRelations($user, $entity);
        return $user;
    }

    public function update(UserEntity $entity): ?User
    {
        /** @var null|User $user */
        $user = $this->userRepository->findById($entity->getId());
        if (! $user) {
            return null;
        }

        $payload = $entity->toArray();
        $payload !== [] && $user->fill($payload)->save();
        $this->syncRelations($user, $entity);
        return $user;
    }

    /**
     * @param array<int> $ids
     */
    public function delete(array $ids): int
    {
        return $this->userRepository->deleteByIds($ids);
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
    }

    /**
     * 获取用户实体.
     */
    public function getEntity(int $id): UserEntity
    {
        /** @var null|User $info */
        $info = $this->userRepository->findById($id);

        return UserMapper::fromModel($info);
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
}
