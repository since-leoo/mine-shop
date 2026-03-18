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

namespace App\Interface\Admin\Controller\Order;

use App\Application\Admin\Trade\AppAfterSaleCommandService;
use App\Application\Admin\Trade\AppAfterSaleQueryService;
use App\Interface\Admin\Transformer\Order\AfterSaleTransformer;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Order\AfterSaleReviewRequest;
use App\Interface\Common\CurrentUser;
use App\Interface\Common\Middleware\AccessTokenMiddleware;
use App\Interface\Common\Middleware\OperationMiddleware;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PutMapping;
use Mine\Access\Attribute\Permission;

#[Controller(prefix: '/admin/order/after-sale')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class AfterSaleController extends AbstractController
{
    public function __construct(
        private readonly AppAfterSaleQueryService $queryService,
        private readonly AppAfterSaleCommandService $commandService,
        private readonly AfterSaleTransformer $transformer,
        private readonly CurrentUser $currentUser,
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'order:after-sale:list')]
    public function list(AfterSaleReviewRequest $request): Result
    {
        $data = $this->queryService->page($request->validated(), $this->getCurrentPage(), $this->getPageSize());

        return $this->success($this->transformer->transformPageResult($data));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'order:after-sale:read')]
    public function show(int $id): Result
    {
        $afterSale = $this->queryService->detail($id);
        if ($afterSale === null) {
            return $this->error('售后单不存在', 404);
        }

        return $this->success($this->transformer->transformDetailResult($afterSale));
    }

    #[PutMapping(path: '{id:\d+}/approve')]
    #[Permission(code: 'order:after-sale:update')]
    public function approve(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->approve($dto), '审核通过成功');
    }

    #[PutMapping(path: '{id:\d+}/reject')]
    #[Permission(code: 'order:after-sale:update')]
    public function reject(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->reject($dto), '审核拒绝成功');
    }

    #[PutMapping(path: '{id:\d+}/receive')]
    #[Permission(code: 'order:after-sale:update')]
    public function receive(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toActionDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->receive($dto), '确认收货成功');
    }

    #[PutMapping(path: '{id:\d+}/refund')]
    #[Permission(code: 'order:after-sale:update')]
    public function refund(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toActionDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->refund($dto), '退款提交成功');
    }

    #[PutMapping(path: '{id:\d+}/reship')]
    #[Permission(code: 'order:after-sale:update')]
    public function reship(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toReshipDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->reship($dto), '补发成功');
    }

    #[PutMapping(path: '{id:\d+}/complete-exchange')]
    #[Permission(code: 'order:after-sale:update')]
    public function completeExchange(int $id, AfterSaleReviewRequest $request): Result
    {
        $dto = $request->toActionDto($id, $this->currentUser->id(), $this->currentUser->user()?->username ?? '系统');

        return $this->success($this->commandService->completeExchange($dto), '换货完成成功');
    }
}
