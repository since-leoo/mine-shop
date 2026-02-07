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
use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Contract\User\UserResetPasswordInput;
use App\Domain\Permission\Entity\UserEntity;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Repository\RoleRepository;
use App\Domain\Permission\Repository\UserRepository;
use App\Infrastructure\Abstract\IService;
use App\Infrastructure\Model\Permission\User;

/**
 * 用户服务类
 * 提供用户相关的业务操作功能.
 */
final class DomainUserService extends IService
{
    /**
     * 构造函数.
     */
    public function __construct(
        protected readonly UserRepository $repository,
        protected readonly RoleRepository $roleRepository
    ) {}

    /**
     * 创建用户.
     *
     * @param UserInput $dto 用户输入 DTO
     * @return User 创建后的用户模型
     */
    public function create(UserInput $dto): User
    {
        $entity = UserMapper::getNewEntity();
        $entity->create($dto);
        $user = $this->repository->create($entity->toArray());
        $this->syncRelations($user, $entity);

        return $user;
    }

    /**
     * 更新用户信息.
     *
     * @param UserInput $dto 用户输入 DTO
     * @return null|User 更新后的用户模型，如果用户不存在则返回null
     */
    public function update(UserInput $dto): ?User
    {
        /** @var null|User $user */
        $user = $this->repository->findById($dto->getId());
        if (! $user) {
            return null;
        }
        $entity = UserMapper::fromModel($user);
        $entity->update($dto);
        $this->repository->updateById($dto->getId(), $entity->toArray());
        $this->syncRelations($user, $entity);

        return $user;
    }

    /**
     * 批量删除用户.
     *
     * @param array<int> $ids 用户ID数组
     * @return int 删除的记录数
     */
    public function delete(array $ids): int
    {
        return $this->repository->deleteByIds($ids);
    }

    /**
     * 重置用户密码
     *
     * @param UserResetPasswordInput $input 密码重置输入对象
     * @return bool 重置是否成功
     */
    public function resetPassword(UserResetPasswordInput $input): bool
    {
        $userEntity = $this->getEntity($input->getUserId());
        $result = $userEntity->resetPasswordWithValidation();

        // 根据验证结果决定是否保存更改
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
     * @param int $id 用户ID
     * @return UserEntity 用户实体对象
     */
    public function getEntity(int $id): UserEntity
    {
        /** @var null|User $info */
        $info = $this->repository->findById($id);

        return UserMapper::fromModel($info);
    }

    /**
     * 同步用户关系数据
     * 包括部门、职位和策略的同步.
     *
     * @param User $user 用户模型
     * @param UserEntity $entity 用户实体对象
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
