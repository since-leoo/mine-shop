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

use App\Domain\Permission\Contract\User\UserGrantRolesInput;
use App\Domain\Permission\Contract\User\UserResetPasswordInput;
use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Repository\RoleRepository;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\User;

/**
 * 用户领域服务.
 *
 * 负责用户的核心业务逻辑，只接受实体对象。
 * DTO 到实体的转换由应用层负责。
 */
final class DomainUserService extends IService
{
    public function __construct(
        protected readonly UserRepository $repository,
        protected readonly RoleRepository $roleRepository
    ) {}

    /**
     * 创建用户.
     *
     * @param UserEntity $entity 用户实体
     * @return User 创建后的用户模型
     */
    public function create(UserEntity $entity): User
    {
        $user = $this->repository->create($entity->toArray());
        $this->syncRelations($user, $entity);
        return $user;
    }

    /**
     * 更新用户信息.
     *
     * @param UserEntity $entity 更新后的实体
     * @return null|User 更新后的用户模型
     */
    public function update(UserEntity $entity): ?User
    {
        /** @var null|User $user */
        $user = $this->repository->findById($entity->getId());
        if (! $user) {
            return null;
        }
        $this->repository->updateById($entity->getId(), $entity->toArray());
        $this->syncRelations($user, $entity);
        return $user;
    }

    /**
     * 批量删除用户.
     *
     * @param array<int> $ids 用户 ID 数组
     * @return int 删除的记录数
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    /**
     * 重置用户密码.
     *
     * @param UserResetPasswordInput $input 密码重置输入对象
     * @return bool 重置是否成功
     */
    public function resetPassword(UserResetPasswordInput $input): bool
    {
        $userEntity = $this->getEntity($input->getUserId());
        $result = $userEntity->resetPasswordWithValidation();

        if ($result->needsSave) {
            $changedData = $userEntity->toArray();
            $this->repository->updateById($userEntity->getId(), $changedData);
        }

        return $result->success;
    }

    /**
     * 授予角色给用户.
     *
     * @param UserGrantRolesInput $input 角色授权输入对象
     */
    public function grantRoles(UserGrantRolesInput $input): void
    {
        $userEntity = $this->getEntity($input->getUserId());
        $roleIds = $this->roleRepository->getRoleIds($input->getRoleCodes());
        $result = $userEntity->grantRoles($roleIds);
        if ($result->success && $result->shouldSync) {
            $this->roleRepository->syncRoles($input->getUserId(), $result->roleIds);
        }
    }

    /**
     * 获取用户实体.
     *
     * @param int $id 用户 ID
     * @return UserEntity 用户实体
     */
    public function getEntity(int $id): UserEntity
    {
        /** @var null|User $info */
        $info = $this->repository->findById($id);

        return UserMapper::fromModel($info);
    }

    /**
     * 同步用户关系数据（部门、职位、策略）.
     */
    private function syncRelations(User $user, UserEntity $entity): void
    {
        // 同步用户部门关系
        if ($entity->shouldSyncDepartments()) {
            $user->department()->sync($entity->getDepartmentIds());
        }
        // 同步用户职位关系
        if ($entity->shouldSyncPositions()) {
            $user->position()->sync($entity->getPositionIds());
        }
        // 同步用户策略关系
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
