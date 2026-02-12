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

use App\Application\Admin\Coupon\AppCouponUserCommandService;
use App\Application\Admin\Coupon\AppCouponUserQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Coupon\CouponUserRequest;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/coupon/user')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class CouponUserController extends AbstractController
{
    public function __construct(
        private readonly AppCouponUserQueryService $queryService,
        private readonly AppCouponUserCommandService $commandService
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'coupon:user:list')]
    public function list(CouponUserRequest $request): Result
    {
        $params = $request->validated();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        unset($params['page'], $params['page_size']);

        return $this->success($this->queryService->page($params, $page, $pageSize));
    }

    #[PutMapping(path: '{id:\d+}/mark-used')]
    #[Permission(code: 'coupon:user:update')]
    public function markUsed(int $id, CouponUserRequest $request): Result
    {
        $request->validated();
        $this->commandService->markUsed($id);
        return $this->success(null, '标记已使用成功');
    }

    #[PutMapping(path: '{id:\d+}/mark-expired')]
    #[Permission(code: 'coupon:user:update')]
    public function markExpired(int $id, CouponUserRequest $request): Result
    {
        $request->validated();
        $this->commandService->markExpired($id);
        return $this->success(null, '标记过期成功');
    }
}
