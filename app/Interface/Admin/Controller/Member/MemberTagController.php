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

use App\Application\Member\Assembler\MemberTagAssembler;
use App\Application\Member\Service\MemberTagCommandService;
use App\Application\Member\Service\MemberTagQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Member\MemberTagRequest;
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

#[Controller(prefix: '/admin/member/tag')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MemberTagController extends AbstractController
{
    public function __construct(
        private readonly MemberTagQueryService $queryService,
        private readonly MemberTagCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'member:tag:list')]
    public function list(MemberTagRequest $request): Result
    {
        $filters = $request->validated();
        $data = $this->queryService->page($filters, $this->getCurrentPage(), $this->getPageSize());
        return $this->success($data);
    }

    #[GetMapping(path: 'options')]
    #[Permission(code: 'member:member:list')]
    public function options(): Result
    {
        return $this->success($this->queryService->activeOptions());
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'member:tag:create')]
    public function store(MemberTagRequest $request): Result
    {
        $payload = $request->validated();
        $entity = MemberTagAssembler::toCreateEntity($payload);
        $tag = $this->commandService->create($entity);
        return $this->success($tag, '标签创建成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:tag:update')]
    public function update(int $id, MemberTagRequest $request): Result
    {
        $payload = $request->validated();
        $entity = MemberTagAssembler::toUpdateEntity($id, $payload);
        $tag = $this->commandService->update($entity);
        return $this->success($tag, '标签更新成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'member:tag:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->delete($id);
        return $this->success(null, '标签已删除');
    }
}
