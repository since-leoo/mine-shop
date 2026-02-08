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

namespace App\Interface\Admin\Controller;

use App\Application\Admin\Permission\AppMenuQueryService;
use App\Application\Admin\Permission\AppRoleQueryService;
use App\Application\Admin\Permission\AppUserCommandService;
use App\Domain\Auth\Enum\Status;
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Admin\Request\Permission\PermissionRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;

#[Controller(prefix: '/admin/permission')]
#[Middleware(AccessTokenMiddleware::class)]
final class PermissionController extends AbstractController
{
    public function __construct(
        private readonly CurrentUser $currentUser,
        private readonly AppMenuQueryService $menuQueryService,
        private readonly AppRoleQueryService $roleQueryService,
        private readonly AppUserCommandService $userCommandService
    ) {}

    #[GetMapping(path: 'menus')]
    public function menus(): Result
    {
        return $this->success(
            data: $this->currentUser->isSuperAdmin()
                ? $this->menuQueryService->list([
                    'status' => Status::Normal,
                    'children' => true,
                    'parent_id' => 0,
                ])
                : $this->currentUser->filterCurrentUser()
        );
    }

    #[GetMapping(path: 'roles')]
    public function roles(): Result
    {
        return $this->success(
            data: $this->currentUser->isSuperAdmin()
                ? $this->roleQueryService->list(['status' => Status::Normal])
                : $this->currentUser->user()->getRoles(['name', 'code', 'remark'])
        );
    }

    #[PostMapping(path: 'update')]
    public function update(PermissionRequest $request): Result
    {
        $data = $request->validated();
        $user = $this->currentUser->user();
        if (Arr::exists($data, 'new_password')) {
            if (! $user->verifyPassword(Arr::get($data, 'old_password'))) {
                throw new BusinessException(ResultCode::UNPROCESSABLE_ENTITY, trans('user.old_password_error'));
            }
            $data['password'] = $data['new_password'];
        }
        $dto = $request->toDto($user->id);
        $this->userCommandService->update($dto);
        return $this->success();
    }
}
