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

namespace App\Interface\Admin\Controller\Review;

use App\Application\Admin\Review\AppReviewCommandService;
use App\Application\Admin\Review\AppReviewQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Review\ReviewReplyRequest;
use App\Interface\Admin\Request\Review\ReviewRequest;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/review')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class ReviewController extends AbstractController
{
    public function __construct(
        private readonly AppReviewQueryService $queryService,
        private readonly AppReviewCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'review:list')]
    public function list(ReviewRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->page($params, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'review:read')]
    public function show(int $id): Result
    {
        return $this->success($this->queryService->findById($id));
    }

    #[PutMapping(path: '{id:\d+}/approve')]
    #[Permission(code: 'review:approve')]
    public function approve(int $id): Result
    {
        $this->commandService->approve($id);
        return $this->success([], '审核通过成功');
    }

    #[PutMapping(path: '{id:\d+}/reject')]
    #[Permission(code: 'review:reject')]
    public function reject(int $id): Result
    {
        $this->commandService->reject($id);
        return $this->success([], '审核拒绝成功');
    }

    #[PutMapping(path: '{id:\d+}/reply')]
    #[Permission(code: 'review:reply')]
    public function reply(int $id, ReviewReplyRequest $request): Result
    {
        $data = $request->validated();
        $this->commandService->reply($id, $data['content']);
        return $this->success([], '回复成功');
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'review:list')]
    public function stats(): Result
    {
        return $this->success($this->queryService->stats());
    }

    #[GetMapping(path: 'by-order/{orderId:\d+}')]
    #[Permission(code: 'review:list')]
    public function byOrder(int $orderId): Result
    {
        return $this->success($this->queryService->listByOrderId($orderId));
    }
}
