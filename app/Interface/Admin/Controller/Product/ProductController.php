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

namespace App\Interface\Admin\Controller\Product;

use App\Application\Commad\ProductCommandService;
use App\Application\Mapper\ProductAssembler;
use App\Application\Query\ProductQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Product\ProductRequest;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/product/product')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductQueryService $queryService,
        private readonly ProductCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'product:product:list')]
    public function list(): Result
    {
        $filters = $this->getRequestData();
        unset($filters['page'], $filters['page_size']);

        return $this->success($this->queryService->page($filters, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'product:product:list')]
    public function stats(): Result
    {
        return $this->success($this->queryService->stats());
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:product:read')]
    public function show(int $id): Result
    {
        $product = $this->queryService->find($id);
        return $product
            ? $this->success($product->toArray())
            : $this->error('商品不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'product:product:create')]
    public function store(ProductRequest $request): Result
    {
        $entity = ProductAssembler::toCreateEntity($request->validated());
        $product = $this->commandService->create($entity);
        return $this->success($product->toArray(), '创建商品成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:product:update')]
    public function update(int $id, ProductRequest $request): Result
    {
        $entity = ProductAssembler::toUpdateEntity($id, $request->validated());
        $this->commandService->update($entity);

        $product = $this->queryService->find($id);
        return $this->success($product?->toArray() ?? [], '更新商品成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:product:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除商品成功');
    }

    #[PutMapping(path: '{id:\d+}/status')]
    #[Permission(code: 'product:product:update')]
    public function updateStatus(int $id, RequestInterface $request): Result
    {
        $entity = ProductAssembler::toUpdateEntity($id, ['status' => $request->input('status')]);
        $this->commandService->update($entity);
        return $this->success(null, '更新状态成功');
    }

    #[PutMapping(path: 'sort')]
    #[Permission(code: 'product:product:update')]
    public function updateSort(RequestInterface $request): Result
    {
        $sortData = $request->input('sort_data', []);
        if (empty($sortData)) {
            return $this->error('排序数据不能为空', 400);
        }

        $this->commandService->updateSort($sortData);
        return $this->success(null, '更新排序成功');
    }
}
