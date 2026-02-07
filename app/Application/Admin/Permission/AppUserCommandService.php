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
use App\Domain\Permission\Service\DomainUserService;
use App\Infrastructure\Model\Permission\User;
use Hyperf\DbConnection\Db;
use Psr\SimpleCache\CacheInterface;

final class AppUserCommandService
{
    public function __construct(
        private readonly DomainUserService $userService,
        private readonly CacheInterface $cache
    ) {}

    public function create(UserInput $input): User
    {
        $user = Db::transaction(fn () => $this->userService->create($input));
        $this->forgetCache((int) $user->id);
        return $user;
    }

    public function update(UserInput $input): ?User
    {
        $user = Db::transaction(fn () => $this->userService->update($input));
        if ($user) {
            $this->forgetCache((int) $user->id);
        }
        return $user;
    }

    public function delete(DeleteInput $input): int
    {
        $deleted = $this->userService->delete($input->getIds());
        foreach ($input->getIds() as $id) {
            $this->forgetCache((int) $id);
        }
        return $deleted;
    }

    public function resetPassword(UserResetPasswordInput $input): bool
    {
        $result = $this->userService->resetPassword($input);
        if ($result) {
            $this->forgetCache($input->getUserId());
        }
        return $result;
    }

    public function grantRoles(UserGrantRolesInput $input): void
    {
        $this->userService->grantRoles($input);
        $this->forgetCache($input->getUserId());
    }

    private function forgetCache(int $id): void
    {
        $this->cache->delete((string) $id);
    }
}
