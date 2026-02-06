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

namespace App\Interface\Admin\Controller\Permission;

use App\Application\Commad\MenuCommandService;
use App\Application\Query\MenuQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Dto\Permission\DeleteDto;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\MenuRequest;
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
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/menu')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class MenuController extends AbstractController
{
    public function __construct(
        private readonly MenuQueryService $queryService,
        private readonly MenuCommandService $commandService,
        private readonly CurrentUser $user
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:menu:index')]
    public function pageList(RequestInterface $request): Result
    {
        return $this->success(data: $this->queryService->list([
            'children' => true,
            'parent_id' => 0,
        ]));
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:menu:create')]
    public function create(MenuRequest $request): Result
    {
        $this->commandService->create($request->toDto(null, $this->user->id()));
        return $this->success();
    }

    #[PutMapping(path: '{id}')]
    #[Permission(code: 'permission:menu:save')]
    public function save(int $id, MenuRequest $request): Result
    {
        $this->commandService->update($request->toDto($id, $this->user->id()));
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:menu:delete')]
    public function delete(): Result
    {
        $requestData = $this->getRequestData();
        $ids = $requestData['ids'] ?? [];
        $dto = new DeleteDto();
        $dto->ids = \is_array($ids) ? $ids : [$ids];
        $dto->operator_id = $this->user->id();

        $this->commandService->delete($dto);
        return $this->success();
    }
}
