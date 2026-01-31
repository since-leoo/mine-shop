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

namespace App\Interface\Admin\Controller\Permission;

use App\Application\Permission\Assembler\PermissionQueryAssembler;
use App\Application\Permission\Assembler\UserAssembler;
use App\Application\Permission\Service\UserCommandService;
use App\Application\Permission\Service\UserQueryService;
use App\Infrastructure\Model\Permission\Role;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\BatchGrantRolesForUserRequest;
use App\Interface\Admin\Request\Permission\UserRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/user')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class UserController extends AbstractController
{
    public function __construct(
        private readonly UserQueryService $queryService,
        private readonly UserCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:user:index')]
    public function pageList(): Result
    {
        return $this->success(
            $this->queryService->paginate(
                PermissionQueryAssembler::toPageQuery(
                    $this->getRequestData(),
                    $this->getCurrentPage(),
                    $this->getPageSize()
                )
            )
        );
    }

    #[PutMapping(path: '')]
    #[Permission(code: 'permission:user:update')]
    public function updateInfo(UserRequest $request): Result
    {
        $entity = UserAssembler::toUpdateEntity(
            $this->currentUser->id(),
            Arr::except($request->validated(), ['password'])
        );
        $this->commandService->update($entity);
        return $this->success();
    }

    #[PutMapping(path: 'password')]
    #[Permission(code: 'permission:user:password')]
    public function resetPassword(): Result
    {
        return $this->commandService->resetPassword((int) $this->getRequest()->input('id'))
            ? $this->success()
            : $this->error();
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:user:save')]
    public function create(UserRequest $request): Result
    {
        $entity = UserAssembler::toCreateEntity(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        $this->commandService->create($entity);
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:user:delete')]
    public function delete(): Result
    {
        $this->commandService->delete($this->getRequestData());
        return $this->success();
    }

    #[PutMapping(path: '{userId}')]
    #[Permission(code: 'permission:user:update')]
    public function save(int $userId, UserRequest $request): Result
    {
        $entity = UserAssembler::toUpdateEntity($userId, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        $this->commandService->update($entity);
        return $this->success();
    }

    #[GetMapping(path: '{userId}/roles')]
    #[Permission(code: 'permission:user:getRole')]
    public function getUserRole(int $userId): Result
    {
        return $this->success($this->queryService->getRoles($userId)->map(static fn (Role $role) => $role->only([
            'id',
            'code',
            'name',
        ])));
    }

    #[PutMapping(path: '{userId}/roles')]
    #[Permission(code: 'permission:user:setRole')]
    public function batchGrantRolesForUser(int $userId, BatchGrantRolesForUserRequest $request): Result
    {
        $this->commandService->grantRoles($userId, $request->input('role_codes'));
        return $this->success();
    }
}
