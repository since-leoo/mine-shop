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

use App\Application\Commad\AppCategoryCommandService;
use App\Application\Query\AppCategoryQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Product\CategoryRequest;
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

#[Controller(prefix: '/admin/product/category')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class CategoryController extends AbstractController
{
    public function __construct(
        private readonly AppCategoryQueryService $queryService,
        private readonly AppCategoryCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'product:category:list')]
    public function list(CategoryRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);
        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: 'tree')]
    #[Permission(code: 'product:category:list')]
    public function tree(CategoryRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->tree((int) ($params['parent_id'] ?? 0)));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:category:read')]
    public function show(int $id): Result
    {
        $category = $this->queryService->findById($id);
        return $category ? $this->success($category->toArray()) : $this->error('分类不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'product:category:create')]
    public function store(CategoryRequest $request): Result
    {
        $category = $this->commandService->create($request->toDto(null));
        return $this->success($category->toArray(), '创建分类成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:category:update')]
    public function update(int $id, CategoryRequest $request): Result
    {
        $this->commandService->update($request->toDto($id));
        return $this->success([], '更新分类成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'product:category:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除分类成功');
    }

    #[GetMapping(path: 'options')]
    #[Permission(code: 'product:category:list')]
    public function options(CategoryRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->options((int) ($params['exclude_id'] ?? 0)));
    }

    #[GetMapping(path: 'statistics')]
    #[Permission(code: 'product:category:list')]
    public function statistics(): Result
    {
        return $this->success($this->queryService->statistics());
    }

    #[PutMapping(path: 'sort')]
    #[Permission(code: 'product:category:update')]
    public function sort(CategoryRequest $request): Result
    {
        $this->commandService->updateSort($request->validated()['sort_data']);
        return $this->success(null, '更新排序成功');
    }

    #[PutMapping(path: 'move')]
    #[Permission(code: 'product:category:update')]
    public function move(CategoryRequest $request): Result
    {
        $params = $request->validated();
        $this->commandService->move((int) $params['category_id'], (int) ($params['parent_id'] ?? 0));
        return $this->success(null, '移动分类成功');
    }

    #[GetMapping(path: '{id:\d+}/breadcrumb')]
    #[Permission(code: 'product:category:list')]
    public function breadcrumb(int $id): Result
    {
        return $this->success($this->queryService->breadcrumb($id));
    }
}
