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

use App\Application\Commad\BrandCommandService;
use App\Application\Mapper\BrandAssembler;
use App\Application\Query\BrandQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Product\BrandRequest;
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

#[Controller(prefix: '/admin/product/brand')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class BrandController extends AbstractController
{
    public function __construct(
        private readonly BrandQueryService $queryService,
        private readonly BrandCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'product:brand:list')]
    public function list(BrandRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->page($params, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:brand:read')]
    public function show(int $id): Result
    {
        $brand = $this->queryService->find($id);
        return $brand ? $this->success($brand->toArray()) : $this->error('品牌不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'product:brand:create')]
    public function store(BrandRequest $request): Result
    {
        $entity = BrandAssembler::toCreateEntity($request->validated());
        $brand = $this->commandService->create($entity);
        return $this->success($brand->toArray(), '创建品牌成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:brand:update')]
    public function update(int $id, BrandRequest $request): Result
    {
        $entity = BrandAssembler::toUpdateEntity($id, $request->validated());
        $this->commandService->update($entity);
        $brand = $this->queryService->find($id);
        return $this->success($brand?->toArray() ?? [], '更新品牌成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:brand:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除品牌成功');
    }

    #[GetMapping(path: 'options')]
    #[Permission(code: 'product:brand:list')]
    public function options(): Result
    {
        return $this->success($this->queryService->options());
    }

    #[GetMapping(path: 'statistics')]
    #[Permission(code: 'product:brand:list')]
    public function statistics(): Result
    {
        return $this->success($this->queryService->statistics());
    }

    #[PutMapping(path: 'sort')]
    #[Permission(code: 'product:brand:update')]
    public function sort(BrandRequest $request): Result
    {
        $this->commandService->updateSort($request->validated()['sort_data']);
        return $this->success(null, '更新排序成功');
    }
}
