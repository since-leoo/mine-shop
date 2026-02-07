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

namespace App\Interface\Admin\Controller\Member;

use App\Application\Commad\MemberLevelCommandService;
use App\Application\Query\MemberLevelQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Member\MemberLevelRequest;
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

#[Controller(prefix: '/admin/member/level')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MemberLevelController extends AbstractController
{
    public function __construct(
        private readonly MemberLevelQueryService $queryService,
        private readonly MemberLevelCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'member:level:list')]
    public function list(MemberLevelRequest $request): Result
    {
        $filters = $request->validated();
        return $this->success($this->queryService->page($filters, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:level:read')]
    public function show(int $id): Result
    {
        $level = $this->queryService->findById($id);
        return $level ? $this->success($level) : $this->error('会员等级不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'member:level:create')]
    public function store(MemberLevelRequest $request): Result
    {
        $level = $this->commandService->create($request->toDto(null, $this->currentUser->id()));
        return $this->success($level, '创建会员等级成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:level:update')]
    public function update(int $id, MemberLevelRequest $request): Result
    {
        $level = $this->commandService->update($request->toDto($id, $this->currentUser->id()));
        return $this->success($level, '更新会员等级成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:level:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '删除会员等级成功');
    }
}
