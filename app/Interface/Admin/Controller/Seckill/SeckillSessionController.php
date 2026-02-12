<?php

declare(strict_types=1);

namespace App\Interface\Admin\Controller\Seckill;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
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
use App\Application\Admin\Seckill\AppSeckillSessionCommandService;
use App\Application\Admin\Seckill\AppSeckillSessionQueryService;
use App\Interface\Admin\Request\Seckill\SeckillSessionRequest;

#[Controller(prefix: '/admin/seckill/session')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SeckillSessionController extends AbstractController
{
    public function __construct(private readonly AppSeckillSessionQueryService $queryService, private readonly AppSeckillSessionCommandService $commandService) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'seckill:session:list')]
    public function list(SeckillSessionRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);
        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: 'by-activity/{activityId:\d+}')]
    #[Permission(code: 'seckill:session:list')]
    public function byActivity(int $activityId): Result
    {
        return $this->success(array_map(static fn ($s) => $s->toArray(), $this->queryService->findByActivityId($activityId)));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:session:read')]
    public function show(int $id): Result
    {
        $session = $this->queryService->find($id);
        return $session ? $this->success($session->toArray()) : $this->error('场次不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'seckill:session:create')]
    public function store(SeckillSessionRequest $request): Result
    {
        $this->commandService->create($request->toDto());
        return $this->success([], '创建场次成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:session:update')]
    public function update(int $id, SeckillSessionRequest $request): Result
    {
        $this->commandService->update($request->toDto($id));
        return $this->success([], '更新场次成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:session:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除场次成功');
    }

    #[PutMapping(path: '{id:\d+}/toggle-status')]
    #[Permission(code: 'seckill:session:update')]
    public function toggleStatus(int $id): Result
    {
        $this->commandService->toggleStatus($id);
        return $this->success(null, '切换状态成功');
    }
}
