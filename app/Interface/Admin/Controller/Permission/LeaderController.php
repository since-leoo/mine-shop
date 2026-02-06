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

use App\Application\Mapper\PermissionQueryAssembler;
use App\Application\Commad\LeaderCommandService;
use App\Application\Query\LeaderQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\LeaderRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/leader')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class LeaderController extends AbstractController
{
    public function __construct(
        protected readonly CurrentUser $currentUser,
        protected readonly LeaderQueryService $queryService,
        protected readonly LeaderCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:leader:index')]
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
    #[Permission(code: 'permission:leader:save')]
    public function create(LeaderRequest $request): Result
    {
        $this->commandService->create(array_merge($request->validated(), [
            'created_by' => $this->currentUser->id(),
        ]));
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:leader:delete')]
    public function delete(): Result
    {
        $this->commandService->delete($this->getRequestData()['dept_id'], $this->getRequestData()['user_ids']);
        return $this->success();
    }
}
