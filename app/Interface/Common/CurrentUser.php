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

namespace App\Interface\Common;

use App\Application\Commad\AuthCommandService;
use App\Application\Query\MenuQueryService;
use App\Application\Query\UserQueryService;
use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Model\Permission\User;
use Hyperf\Context\Context;
use Lcobucci\JWT\Token\RegisteredClaims;
use Mine\Jwt\Traits\RequestScopedTokenTrait;

final class CurrentUser
{
    use RequestScopedTokenTrait;

    public function __construct(
        private readonly AuthCommandService $authCommandService,
        private readonly UserQueryService $userQueryService,
        private readonly MenuQueryService $menuQueryService
    ) {}

    public static function ctxUser(): ?User
    {
        return Context::get('current_user');
    }

    public function user(): ?User
    {
        if (Context::has('current_user')) {
            return Context::get('current_user');
        }
        $user = $this->userQueryService->findCached($this->id());
        Context::set('current_user', $user);
        return $user;
    }

    public function refresh(): array
    {
        return $this->authCommandService->refresh($this->getToken())->toArray();
    }

    public function id(): int
    {
        return (int) $this->getToken()->claims()->get(RegisteredClaims::ID);
    }

    public function isSuperAdmin(): bool
    {
        return $this->user()->isSuperAdmin();
    }

    public function filterCurrentUser(): array
    {
        $permissions = $this->user()
            ->getPermissions()
            ->pluck('name')
            ->unique();
        $menuList = $permissions->isEmpty()
            ? []
            : $this->menuQueryService
                ->list(['status' => Status::Normal, 'name' => $permissions->toArray()])
                ->toArray();
        $tree = [];
        $map = [];
        foreach ($menuList as &$menu) {
            $menu['children'] = [];
            $map[$menu['id']] = &$menu;
        }
        unset($menu);
        foreach ($menuList as &$menu) {
            $pid = $menu['parent_id'];
            if ($pid === 0 || ! isset($map[$pid])) {
                $tree[] = &$menu;
            } else {
                $map[$pid]['children'][] = &$menu;
            }
        }
        unset($menu);
        return $tree;
    }
}
