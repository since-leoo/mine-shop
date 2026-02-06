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

use App\Application\Commad\RoleCommandService;
use App\Application\Mapper\PermissionQueryAssembler;
use App\Application\Mapper\RoleAssembler;
use App\Application\Query\RoleQueryService;
use App\Infrastructure\Exception\System\BusinessException;
use App\Infrastructure\Model\Permission\Menu;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\BatchGrantPermissionsForRoleRequest;
use App\Interface\Admin\Request\Permission\RoleRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use App\Interface\Common\ResultCode;
use Hyperf\Collection\Arr;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/role')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class RoleController extends AbstractController
{
    public function __construct(
        private readonly RoleQueryService $queryService,
        private readonly RoleCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:role:index')]
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

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:role:save')]
    public function create(RoleRequest $request): Result
    {
        $entity = RoleAssembler::toCreateEntity(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        $this->commandService->create($entity);
        return $this->success();
    }

    #[PutMapping(path: '{id}')]
    #[Permission(code: 'permission:role:update')]
    public function save(int $id, RoleRequest $request): Result
    {
        $entity = RoleAssembler::toUpdateEntity($id, array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        $this->commandService->update($id, $entity);
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:role:delete')]
    public function delete(): Result
    {
        $this->commandService->delete($this->getRequestData());
        return $this->success();
    }

    #[GetMapping(path: '{id}/permissions')]
    #[Permission(code: 'permission:role:getMenu')]
    public function getRolePermissionForRole(int $id): Result
    {
        return $this->success($this->queryService->permissions($id)->map(static fn (Menu $menu) => $menu->only([
            'id', 'name',
        ]))->toArray());
    }

    #[PutMapping(path: '{id}/permissions')]
    #[Permission(code: 'permission:role:setMenu')]
    public function batchGrantPermissionsForRole(int $id, BatchGrantPermissionsForRoleRequest $request): Result
    {
        if (! $this->queryService->find($id)) {
            throw new BusinessException(code: ResultCode::NOT_FOUND);
        }
        $permissionsCode = Arr::get($request->validated(), 'permissions', []);
        $this->commandService->grantPermissions($id, $permissionsCode);
        return $this->success();
    }
}
