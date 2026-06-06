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

use App\Application\Admin\Content\AppDiyPageCommandService;
use App\Application\Admin\Content\AppDiyPageQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\DiyPage\DiyPageRequest;
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

#[Controller(prefix: '/admin/diy/pages')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DiyPageController extends AbstractController
{
    public function __construct(
        private readonly AppDiyPageQueryService $queryService,
        private readonly AppDiyPageCommandService $commandService,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'mall:diy:read')]
    public function list(DiyPageRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 10);
        unset($params['page'], $params['page_size']);

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'mall:diy:read')]
    public function show(int $id): Result
    {
        $page = $this->queryService->find($id);

        return $page ? $this->success($page->toArray()) : $this->error('DIY页面不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'mall:diy:create')]
    public function store(DiyPageRequest $request): Result
    {
        $page = $this->commandService->create($request->toDto(), $this->currentUser->id());

        return $this->success($page->toArray(), '创建DIY页面成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'mall:diy:update')]
    public function update(int $id, DiyPageRequest $request): Result
    {
        $this->commandService->update($id, $request->toDto(), $this->currentUser->id());

        return $this->success([], '更新DIY页面成功');
    }

    #[PutMapping(path: '{id:\d+}/draft')]
    #[Permission(code: 'mall:diy:update')]
    public function draft(int $id, DiyPageRequest $request): Result
    {
        $version = $this->commandService->saveDraft($id, $request->toDraftDto(), $this->currentUser->id());

        return $this->success($version->toArray(), '保存草稿成功');
    }

    #[PostMapping(path: '{id:\d+}/publish')]
    #[Permission(code: 'mall:diy:publish')]
    public function publish(int $id): Result
    {
        $version = $this->commandService->publish($id, $this->currentUser->id());

        return $this->success($version->toArray(), '发布DIY页面成功');
    }

    #[PostMapping(path: '{id:\d+}/enable')]
    #[Permission(code: 'mall:diy:enable')]
    public function enable(int $id): Result
    {
        $this->commandService->enable($id, $this->currentUser->id());

        return $this->success([], '启用DIY页面成功');
    }

    #[PostMapping(path: '{id:\d+}/disable')]
    #[Permission(code: 'mall:diy:enable')]
    public function disable(int $id): Result
    {
        $this->commandService->disable($id, $this->currentUser->id());

        return $this->success([], '禁用DIY页面成功');
    }

    #[PostMapping(path: '{id:\d+}/copy')]
    #[Permission(code: 'mall:diy:create')]
    public function copy(int $id): Result
    {
        $page = $this->commandService->copy($id, $this->currentUser->id());

        return $this->success($page->toArray(), '复制DIY页面成功', 201);
    }

    #[PostMapping(path: '{id:\d+}/reset')]
    #[Permission(code: 'mall:diy:update')]
    public function reset(int $id): Result
    {
        $version = $this->commandService->resetDraft($id, $this->currentUser->id());

        return $this->success($version->toArray(), '重置草稿成功');
    }
}
