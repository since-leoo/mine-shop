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

namespace App\Interface\Admin\Controller\GroupBuy;

use App\Application\Admin\GroupBuy\AppGroupBuyCommandService;
use App\Application\Admin\GroupBuy\AppGroupBuyQueryService;
use App\Application\Admin\GroupBuy\Dto\GroupBuyExportDto;
use App\Infrastructure\Error\GroupBuyError;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\GroupBuy\GroupBuyRequest;
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
use Plugin\ExportCenter\Service\ExportService;

#[Controller(prefix: '/admin/group-buy')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class GroupBuyController extends AbstractController
{
    public function __construct(
        private readonly AppGroupBuyQueryService $queryService,
        private readonly AppGroupBuyCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'promotion:group_buy:list')]
    public function list(GroupBuyRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);
        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'promotion:group_buy:list')]
    public function stats(): Result
    {
        return $this->success($this->queryService->stats());
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'promotion:group_buy:read')]
    public function show(int $id): Result
    {
        $groupBuy = $this->queryService->find($id);
        return $groupBuy
            ? $this->success($groupBuy->toArray())
            : $this->error(GroupBuyError::NOT_FOUND, 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'promotion:group_buy:create')]
    public function store(GroupBuyRequest $request): Result
    {
        $act = $this->commandService->create($request->toDto(null));
        return $act ? $this->success([], '创建团购活动成功', 201) : $this->error(GroupBuyError::CREATE_FAILED);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'promotion:group_buy:update')]
    public function update(int $id, GroupBuyRequest $request): Result
    {
        $act = $this->commandService->update($request->toDto($id));
        return $act ? $this->success([], '更新团购活动成功') : $this->error(GroupBuyError::UPDATE_FAILED);
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'promotion:group_buy:delete')]
    public function delete(int $id): Result
    {
        $act = $this->commandService->delete($id);
        return $act ? $this->success(null, '删除团购活动成功') : $this->error(GroupBuyError::DELETE_FAILED);
    }

    #[PutMapping(path: '{id:\d+}/toggle-status')]
    #[Permission(code: 'promotion:group_buy:update')]
    public function toggleStatus(int $id): Result
    {
        $act = $this->commandService->toggleStatus($id);
        return $act ? $this->success(null, '切换状态成功') : $this->error(GroupBuyError::TOGGLE_FAILED);
    }

    #[PostMapping(path: 'export')]
    #[Permission(code: 'promotion:group_buy:list')]
    public function export(GroupBuyRequest $request): Result
    {
        $task = di(ExportService::class)->export(
            userId: $this->currentUser->id(),
            taskName: '团购活动导出',
            dtoClass: GroupBuyExportDto::class,
            params: $request->validated(),
        );

        return $this->success(['task_id' => $task->id, 'status' => $task->status]);
    }
}
