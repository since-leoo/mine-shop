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

namespace App\Interface\Admin\Controller\Seckill;

use App\Application\Admin\Marketing\AppSeckillActivityCommandService;
use App\Application\Admin\Marketing\AppSeckillActivityQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Seckill\SeckillActivityRequest;
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

#[Controller(prefix: '/admin/seckill/activity')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SeckillActivityController extends AbstractController
{
    public function __construct(
        private readonly AppSeckillActivityQueryService $queryService,
        private readonly AppSeckillActivityCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'seckill:activity:list')]
    public function list(SeckillActivityRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'seckill:activity:list')]
    public function stats(): Result
    {
        return $this->success($this->queryService->stats());
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:activity:read')]
    public function show(int $id): Result
    {
        $activity = $this->queryService->find($id);
        return $activity
            ? $this->success($activity->toArray())
            : $this->error('活动不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'seckill:activity:create')]
    public function store(SeckillActivityRequest $request): Result
    {
        $this->commandService->create($request->toDto());
        return $this->success([], '创建活动成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:activity:update')]
    public function update(int $id, SeckillActivityRequest $request): Result
    {
        $this->commandService->update($request->toDto($id));
        return $this->success([], '更新活动成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:activity:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除活动成功');
    }

    #[PutMapping(path: '{id:\d+}/toggle-status')]
    #[Permission(code: 'seckill:activity:update')]
    public function toggleStatus(int $id): Result
    {
        $this->commandService->toggleStatus($id);
        return $this->success(null, '切换状态成功');
    }
}
