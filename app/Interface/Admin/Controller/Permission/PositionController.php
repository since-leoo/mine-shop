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

use App\Application\Commad\PositionCommandService;
use App\Application\Mapper\PermissionQueryAssembler;
use App\Application\Mapper\PositionAssembler;
use App\Application\Query\PositionQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\BatchGrantDataPermissionForPositionRequest;
use App\Interface\Admin\Request\Permission\PositionRequest;
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

#[Controller(prefix: '/admin/position')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class PositionController extends AbstractController
{
    public function __construct(
        protected readonly CurrentUser $currentUser,
        protected readonly PositionQueryService $queryService,
        protected readonly PositionCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:position:index')]
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

    #[PutMapping(path: '{id}/data_permission')]
    #[Permission(code: 'permission:position:data_permission')]
    public function batchDataPermission(int $id, BatchGrantDataPermissionForPositionRequest $request): Result
    {
        $this->commandService->setDataPermission($request->toDto($id, $this->currentUser->id()));
        return $this->success();
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:position:save')]
    public function create(PositionRequest $request): Result
    {
        $payload = PositionAssembler::fromArray(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        $this->commandService->create($payload);
        return $this->success();
    }

    #[PutMapping(path: '{id}')]
    #[Permission(code: 'permission:position:update')]
    public function save(int $id, PositionRequest $request): Result
    {
        $payload = PositionAssembler::fromArray(array_merge($request->validated(), [
            'updated_by' => $this->currentUser->id(),
        ]));
        $this->commandService->update($id, $payload);
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:position:delete')]
    public function delete(): Result
    {
        $this->commandService->delete($this->getRequestData());
        return $this->success();
    }
}
