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

use App\Application\Commad\DepartmentCommandService;
use App\Application\Mapper\DepartmentAssembler;
use App\Application\Query\DepartmentQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\DepartmentRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/department')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class DepartmentController extends AbstractController
{
    public function __construct(
        protected readonly CurrentUser $currentUser,
        protected readonly DepartmentQueryService $queryService,
        protected readonly DepartmentCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:department:index')]
    public function pageList(): Result
    {
        return $this->success([
            'list' => $this->queryService->list($this->getRequestData()),
        ]);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:department:save')]
    public function create(DepartmentRequest $request): Result
    {
        $payload = DepartmentAssembler::fromArray(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        $this->commandService->create($payload, static function ($entity, array $data): void {
            // 复用原关系同步逻辑
            $entity->department_users()->sync($data['department_users'] ?? []);
            $entity->leader()->sync($data['leader'] ?? []);
        });
        return $this->success();
    }

    #[PutMapping(path: '{id}')]
    #[Permission(code: 'permission:department:update')]
    public function save(int $id, DepartmentRequest $request): Result
    {
        $payload = DepartmentAssembler::fromArray(array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        $this->commandService->update($id, $payload, static function ($entity, array $data): void {
            $entity->department_users()->sync($data['department_users'] ?? []);
            $entity->leader()->sync($data['leader'] ?? []);
        });
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:department:delete')]
    public function delete(): Result
    {
        $this->commandService->delete($this->getRequestData());
        return $this->success();
    }
}
