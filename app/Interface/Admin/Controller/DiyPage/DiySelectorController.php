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

use App\Application\Admin\Content\AppDiySelectorQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\DiyPage\DiySelectorRequest;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/diy/selectors')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class DiySelectorController extends AbstractController
{
    public function __construct(private readonly AppDiySelectorQueryService $queryService) {}

    #[GetMapping(path: 'products')]
    #[Permission(code: 'mall:diy:page:update')]
    public function products(DiySelectorRequest $request): Result
    {
        [$params, $page, $pageSize] = $this->resolvePageParams($request->validated());

        return $this->success($this->queryService->products($params, $page, $pageSize));
    }

    #[GetMapping(path: 'categories')]
    #[Permission(code: 'mall:diy:page:update')]
    public function categories(DiySelectorRequest $request): Result
    {
        return $this->success($this->queryService->categories($request->validated()));
    }

    #[GetMapping(path: 'coupons')]
    #[Permission(code: 'mall:diy:page:update')]
    public function coupons(DiySelectorRequest $request): Result
    {
        [$params, $page, $pageSize] = $this->resolvePageParams($request->validated());

        return $this->success($this->queryService->coupons($params, $page, $pageSize));
    }

    #[GetMapping(path: 'seckills')]
    #[Permission(code: 'mall:diy:page:update')]
    public function seckills(DiySelectorRequest $request): Result
    {
        [$params, $page, $pageSize] = $this->resolvePageParams($request->validated());

        return $this->success($this->queryService->seckills($params, $page, $pageSize));
    }

    #[GetMapping(path: 'group-buys')]
    #[Permission(code: 'mall:diy:page:update')]
    public function groupBuys(DiySelectorRequest $request): Result
    {
        [$params, $page, $pageSize] = $this->resolvePageParams($request->validated());

        return $this->success($this->queryService->groupBuys($params, $page, $pageSize));
    }

    /**
     * @return array{array<string, mixed>, int, int}
     */
    private function resolvePageParams(array $params): array
    {
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 10);
        unset($params['page'], $params['page_size']);

        return [$params, $page, $pageSize];
    }
}
