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

use App\Application\Commad\SeckillProductCommandService;
use App\Application\Query\SeckillProductQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Seckill\SeckillProductRequest;
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

#[Controller(prefix: '/admin/seckill/product')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SeckillProductController extends AbstractController
{
    public function __construct(
        private readonly SeckillProductQueryService $queryService,
        private readonly SeckillProductCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'seckill:product:list')]
    public function list(SeckillProductRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: 'by-session/{sessionId:\d+}')]
    #[Permission(code: 'seckill:product:list')]
    public function bySession(int $sessionId): Result
    {
        $products = $this->queryService->findBySessionId($sessionId);
        return $this->success(array_map(static fn ($p) => $p->toArray(), $products));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:product:read')]
    public function show(int $id): Result
    {
        $product = $this->queryService->find($id);
        return $product
            ? $this->success($product->toArray())
            : $this->error('商品不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'seckill:product:create')]
    public function store(SeckillProductRequest $request): Result
    {
        $this->commandService->create($request->toDto());
        return $this->success([], '添加商品成功', 201);
    }

    #[PostMapping(path: 'batch')]
    #[Permission(code: 'seckill:product:create')]
    public function batchStore(SeckillProductRequest $request): Result
    {
        $data = $request->validated();
        $this->commandService->batchCreate(
            (int) $data['activity_id'],
            (int) $data['session_id'],
            $data['products']
        );
        return $this->success([], '批量添加商品成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:product:update')]
    public function update(int $id, SeckillProductRequest $request): Result
    {
        $this->commandService->update($request->toDto($id));
        return $this->success([], '更新商品成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'seckill:product:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除商品成功');
    }

    #[PutMapping(path: '{id:\d+}/toggle-status')]
    #[Permission(code: 'seckill:product:update')]
    public function toggleStatus(int $id): Result
    {
        $this->commandService->toggleStatus($id);
        return $this->success(null, '切换状态成功');
    }
}
