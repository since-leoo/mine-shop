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

namespace App\Interface\Admin\Controller\Coupon;

use App\Application\Admin\Coupon\AppCouponCommandService;
use App\Application\Admin\Coupon\AppCouponQueryService;
use App\Application\Admin\Coupon\AppCouponUserCommandService;
use App\Application\Admin\Coupon\Dto\CouponExportDto;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Coupon\CouponIssueRequest;
use App\Interface\Admin\Request\Coupon\CouponRequest;
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
use Plugin\ExportCenter\Service\ExportService;

#[Controller(prefix: '/admin/coupon')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class CouponController extends AbstractController
{
    public function __construct(
        private readonly AppCouponQueryService $queryService,
        private readonly AppCouponCommandService $commandService,
        private readonly AppCouponUserCommandService $couponUserCommandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'coupon:list')]
    public function list(CouponRequest $request): Result
    {
        $params = $request->validated();
        return $this->success($this->queryService->page($params, $this->getCurrentPage(), $this->getPageSize()));
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'coupon:list')]
    public function stats(): Result
    {
        return $this->success($this->queryService->stats());
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'coupon:read')]
    public function show(int $id): Result
    {
        $coupon = $this->queryService->findById($id);
        return $coupon ? $this->success($coupon) : $this->error('优惠券不存在', 404);
    }

    #[PostMapping(path: '')]
    #[Permission(code: 'coupon:create')]
    public function store(CouponRequest $request): Result
    {
        $this->commandService->create($request->toDto());
        return $this->success([], '创建优惠券成功', 201);
    }

    #[PutMapping(path: '{id:\d+}')]
    #[Permission(code: 'coupon:update')]
    public function update(int $id, CouponRequest $request): Result
    {
        $this->commandService->update($request->toDto($id));

        return $this->success([], '更新优惠券成功');
    }

    #[DeleteMapping(path: '{id:\d+}')]
    #[Permission(code: 'coupon:delete')]
    public function delete(int $id): Result
    {
        $this->commandService->deleteById($id);
        return $this->success(null, '删除优惠券成功');
    }

    #[PutMapping(path: '{id:\d+}/toggle-status')]
    #[Permission(code: 'coupon:update')]
    public function toggleStatus(int $id): Result
    {
        $this->commandService->toggleStatus($id);
        return $this->success(null, '切换状态成功');
    }

    #[PostMapping(path: '{id:\d+}/issue')]
    #[Permission(code: 'coupon:issue')]
    public function issue(int $id, CouponIssueRequest $request): Result
    {
        $data = $request->validated();
        $result = $this->couponUserCommandService->issue($id, $data['member_ids'], $data['expire_at'] ?? null);
        return $this->success(
            array_map(static fn ($item) => $item->toArray(), $result),
            '发放成功'
        );
    }

    #[PostMapping(path: 'export')]
    #[Permission(code: 'coupon:list')]
    public function export(CouponRequest $request): Result
    {
        $task = di(ExportService::class)->export(
            userId: $this->currentUser->id(),
            taskName: '优惠券导出',
            dtoClass: CouponExportDto::class,
            params: $request->validated(),
        );

        return $this->success(['task_id' => $task->id, 'status' => $task->status]);
    }
}
