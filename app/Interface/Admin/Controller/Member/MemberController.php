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

use App\Application\Admin\Member\AppMemberCommandService;
use App\Application\Admin\Member\AppMemberQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Member\MemberRequest;
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

#[Controller(prefix: '/admin/member/member')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MemberController extends AbstractController
{
    public function __construct(
        private readonly AppMemberQueryService $queryService,
        private readonly AppMemberCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[PostMapping(path: '')]
    #[Permission(code: 'member:member:create')]
    public function store(MemberRequest $request): Result
    {
        $member = $this->commandService->create($request->toDto(null, $this->currentUser->id()));
        return $this->success($member, '会员已创建', 201);
    }

    #[GetMapping(path: 'list')]
    #[Permission(code: 'member:member:list')]
    public function list(MemberRequest $request): Result
    {
        $filters = $request->validated();
        $data = $this->queryService->page($filters, $this->getCurrentPage(), $this->getPageSize());
        return $this->success($data);
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'member:member:list')]
    public function stats(MemberRequest $request): Result
    {
        $filters = $request->validated();
        return $this->success($this->queryService->stats($filters));
    }

    #[GetMapping(path: 'overview')]
    #[Permission(code: 'member:member:list')]
    public function overview(MemberRequest $request): Result
    {
        $filters = $request->validated();
        return $this->success($this->queryService->overview($filters));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:member:read')]
    public function show(int $id): Result
    {
        $member = $this->queryService->detail($id);
        if (! $member) {
            return $this->error('会员不存在', 404);
        }

        return $this->success($member);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:member:update')]
    public function update(int $id, MemberRequest $request): Result
    {
        $member = $this->commandService->update($request->toDto($id, $this->currentUser->id()));
        return $this->success($member, '会员资料已更新');
    }

    #[PutMapping(path: '{id:\d+}/status')]
    #[Permission(code: 'member:member:update')]
    public function updateStatus(int $id, MemberRequest $request): Result
    {
        $payload = $request->validated();
        $this->commandService->updateStatus($id, (string) $payload['status']);
        return $this->success([], '会员状态已更新');
    }

    #[PutMapping(path: '{id:\d+}/tags')]
    #[Permission(code: 'member:member:tag')]
    public function syncTags(int $id, MemberRequest $request): Result
    {
        $payload = $request->validated();
        $tags = \is_array($payload['tags'] ?? null) ? $payload['tags'] : [];
        $this->commandService->syncTags($id, $tags);
        return $this->success([], '会员标签已更新');
    }
}
