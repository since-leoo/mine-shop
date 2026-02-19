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

namespace App\Application\Admin\Permission;

use App\Domain\Permission\Contract\Common\DeleteInput;
use App\Domain\Permission\Contract\User\UserGrantRolesInput;
use App\Domain\Permission\Contract\User\UserInput;
use App\Domain\Permission\Contract\User\UserResetPasswordInput;
use App\Domain\Permission\Mapper\UserMapper;
use App\Domain\Permission\Service\DomainUserService;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

/**
 * 用户应用层命令服务.
 *
 * 负责协调领域服务，处理 DTO 到实体的转换。
 */
final class AppUserCommandService
{
    public function __construct(
        private readonly DomainUserService $userService,
        private readonly CacheInterface $cache
    ) {}

    /**
     * 创建用户.
     *
     * @param UserInput $input 用户输入 DTO
     * @return User 创建的用户模型
     */
    public function create(UserInput $input): User
    {
        // 使用 Mapper 将 DTO 转换为实体
        $entity = UserMapper::fromDto($input);
        $user = Db::transaction(fn () => $this->userService->create($entity));
        $this->forgetCache((int) $user->id);
        return $user;
    }

    /**
     * 更新用户.
     *
     * @param UserInput $input 用户输入 DTO
     * @return null|User 更新后的用户模型
     */
    public function update(UserInput $input): ?User
    {
        // 从数据库获取实体并更新
        $entity = $this->userService->getEntity($input->getId());
        $entity->update($input);
        $user = Db::transaction(fn () => $this->userService->update($entity));
        if ($user) {
            $this->forgetCache((int) $user->id);
        }
        return $user;
    }

    /**
     * 删除用户.
     *
     * @param DeleteInput $input 删除输入
     * @return int 删除的记录数
     */
    public function delete(DeleteInput $input): int
    {
        $deleted = $this->userService->delete($input->getIds());
        foreach ($input->getIds() as $id) {
            $this->forgetCache((int) $id);
        }
        return $deleted;
    }

    /**
     * 重置用户密码.
     *
     * @param UserResetPasswordInput $input 密码重置输入
     * @return bool 是否重置成功
     */
    public function resetPassword(UserResetPasswordInput $input): bool
    {
        $result = $this->userService->resetPassword($input);
        if ($result) {
            $this->forgetCache($input->getUserId());
        }
        return $result;
    }

    /**
     * 授予角色给用户.
     *
     * @param UserGrantRolesInput $input 角色授权输入
     */
    public function grantRoles(UserGrantRolesInput $input): void
    {
        $this->userService->grantRoles($input);
        $this->forgetCache($input->getUserId());
    }

    /**
     * 清除用户缓存.
     */
    private function forgetCache(int $id): void
    {
        $this->cache->delete((string) $id);
    }
}
