<?php

declare(strict_types=1);

namespace Plugin\Since\Seckill\Interface\Controller\Admin;

use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Contract\RequestInterface;
use Mine\Access\Attribute\Permission;
use Plugin\Since\Seckill\Application\Admin\AppSeckillOrderQueryService;

#[Controller(prefix: '/admin/seckill-order')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class SeckillOrderController extends AbstractController
{
    public function __construct(
        private readonly AppSeckillOrderQueryService $queryService,
        private readonly RequestInterface $request,
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'promotion:seckill_order:list')]
    public function list(): Result
    {
        $params = $this->request->all();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        $filters = array_intersect_key($params, array_flip(['title', 'status']));

        return $this->success($this->queryService->activitySummaryPage($filters, $page, $pageSize));
    }

    #[GetMapping(path: '{activityId:\d+}/orders')]
    #[Permission(code: 'promotion:seckill_order:list')]
    public function orders(int $activityId): Result
    {
        $params = $this->request->all();
        $page = (int) ($params['page'] ?? 1);
        $pageSize = (int) ($params['page_size'] ?? 15);
        $filters = array_intersect_key($params, array_flip(['status']));

        return $this->success($this->queryService->ordersByActivity($activityId, $filters, $page, $pageSize));
    }
}
