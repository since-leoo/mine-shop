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

namespace App\Interface\Admin\Controller\DiyPage;

use App\Application\Admin\Content\AppDiyTemplateCommandService;
use App\Application\Admin\Content\AppDiyTemplateQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\DiyPage\DiyTemplateRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/diy/templates')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DiyTemplateController extends AbstractController
{
    public function __construct(
        private readonly AppDiyTemplateQueryService $queryService,
        private readonly AppDiyTemplateCommandService $commandService,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'mall:diy:template:read')]
    public function list(DiyTemplateRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 10);
        unset($params['page'], $params['page_size']);

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'mall:diy:template:read')]
    public function show(int $id): Result
    {
        $template = $this->queryService->find($id);

        return $template ? $this->success($template->toArray()) : $this->error('装修模板不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'mall:diy:template:create')]
    public function store(DiyTemplateRequest $request): Result
    {
        $template = $this->commandService->create($request->toDto(), $this->currentUser->id());

        return $this->success($template->toArray(), '创建装修模板成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'mall:diy:template:update')]
    public function update(int $id, DiyTemplateRequest $request): Result
    {
        $this->commandService->update($id, $request->toDto(), $this->currentUser->id());

        return $this->success([], '更新装修模板成功');
    }

    #[PostMapping(path: '{id:\d+}/enable')]
    #[Permission(code: 'mall:diy:template:update')]
    public function enable(int $id): Result
    {
        $this->commandService->enable($id);

        return $this->success([], '启用装修模板成功');
    }

    #[PostMapping(path: '{id:\d+}/disable')]
    #[Permission(code: 'mall:diy:template:update')]
    public function disable(int $id): Result
    {
        $this->commandService->disable($id);

        return $this->success([], '禁用装修模板成功');
    }

    #[PostMapping(path: '{id:\d+}/apply')]
    #[Permission(code: 'mall:diy:page:update')]
    public function apply(int $id, DiyTemplateRequest $request): Result
    {
        $version = $this->commandService->apply($request->toApplyDto($id), $this->currentUser->id());

        return $this->success($version->toArray(), '套用装修模板成功');
    }
}
