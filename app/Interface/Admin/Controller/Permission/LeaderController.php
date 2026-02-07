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

use App\Application\Commad\LeaderCommandService;
use App\Application\Query\LeaderQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Dto\Permission\LeaderDeleteDto;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Permission\LeaderRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\DeleteMapping;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/leader')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
class LeaderController extends AbstractController
{
    public function __construct(
        protected readonly CurrentUser $currentUser,
        protected readonly LeaderQueryService $queryService,
        protected readonly LeaderCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'permission:leader:index')]
    public function pageList(): Result
    {
        $params = $this->getRequestData();
        return $this->success(
            $this->queryService->page($params, $this->getCurrentPage(), $this->getPageSize())
        );
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'permission:leader:save')]
    public function create(LeaderRequest $request): Result
    {
        $this->commandService->create($request->toDto($this->currentUser->id()));
        return $this->success();
    }

    #[DeleteMapping(path: '')]
    #[Permission(code: 'permission:leader:delete')]
    public function delete(): Result
    {
        $requestData = $this->getRequestData();
        $userIds = $requestData['user_ids'] ?? [];
        $dto = new LeaderDeleteDto();
        $dto->dept_id = $requestData['dept_id'] ?? 0;
        $dto->user_ids = \is_array($userIds) ? $userIds : [$userIds];
        $dto->operator_id = $this->currentUser->id();

        $this->commandService->delete($dto);
        return $this->success();
    }
}
