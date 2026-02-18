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

namespace App\Interface\Admin\Controller\Shipping;

use App\Application\Admin\Shipping\AppShippingTemplateCommandService;
use App\Application\Admin\Shipping\AppShippingTemplateQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Shipping\ShippingTemplateRequest;
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

#[Controller(prefix: '/admin/shipping/templates')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class ShippingTemplateController extends AbstractController
{
    public function __construct(
        private readonly AppShippingTemplateQueryService $queryService,
        private readonly AppShippingTemplateCommandService $commandService,
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'shipping:template:list')]
    public function list(ShippingTemplateRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->page($params, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'shipping:template:read')]
    public function show(int $id): Result
    {
        $entity = $this->queryService->getById($id);
        return $this->success(array_merge(['id' => $entity->getId()], $entity->toArray()));
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'shipping:template:create')]
    public function store(ShippingTemplateRequest $request): Result
    {
        $entity = $this->commandService->create($request->toDto(null));
        return $this->success(array_merge(['id' => $entity->getId()], $entity->toArray()), '创建运费模板成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'shipping:template:update')]
    public function update(int $id, ShippingTemplateRequest $request): Result
    {
        $this->commandService->update($id, $request->toDto($id));
        return $this->success([], '更新运费模板成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'shipping:template:delete')]
    public function destroy(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除运费模板成功');
    }
}
