<?php

declare(strict_types=1);

namespace App\Interface\Admin\Controller\Order;

use App\Application\Order\Assembler\OrderAssembler;
use App\Application\Order\Service\OrderCommandService;
use App\Application\Order\Service\OrderQueryService;
use App\Interface\Admin\Controller\AbstractController;
use App\Interface\Admin\Middleware\PermissionMiddleware;
use App\Interface\Admin\Request\Order\OrderRequest;
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

#[Controller(prefix: '/admin/order/order')]
#[Middleware(middleware: AccessTokenMiddleware::class, priority: 100)]
#[Middleware(middleware: PermissionMiddleware::class, priority: 99)]
#[Middleware(middleware: OperationMiddleware::class, priority: 98)]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderQueryService $queryService,
        private readonly OrderCommandService $commandService,
        private readonly CurrentUser $currentUser
    ) {}

    #[GetMapping(path: 'list')]
    #[Permission(code: 'order:order:list')]
    public function list(OrderRequest $request): Result
    {
        $filters = $request->validated();
        $data = $this->queryService->page($filters, $this->getCurrentPage(), $this->getPageSize());
        return $this->success($data);
    }

    #[GetMapping(path: 'stats')]
    #[Permission(code: 'order:order:list')]
    public function stats(OrderRequest $request): Result
    {
        $filters = $request->validated();
        return $this->success($this->queryService->stats($filters));
    }

    #[GetMapping(path: '{id:\d+}')]
    #[Permission(code: 'order:order:read')]
    public function show(int $id): Result
    {
        $order = $this->queryService->detail($id);
        if (! $order) {
            return $this->error('订单不存在', 404);
        }
        return $this->success($order);
    }

    #[PutMapping(path: '{id:\d+}/ship')]
    #[Permission(code: 'order:order:update')]
    public function ship(int $id, OrderRequest $request): Result
    {
        $payload = $request->validated();
        $operator = [
            'id' => $this->currentUser->id(),
            'name' => $this->currentUser->user()?->username ?? '管理员',
        ];
        $entity = OrderAssembler::toShipEntity($id, $payload, $operator);
        $order = $this->commandService->ship($entity);
        return $this->success($order, '发货成功');
    }

    #[PutMapping(path: '{id:\d+}/cancel')]
    #[Permission(code: 'order:order:update')]
    public function cancel(int $id, OrderRequest $request): Result
    {
        $payload = $request->validated();
        $operator = [
            'id' => $this->currentUser->id(),
            'name' => $this->currentUser->user()?->username ?? '管理员',
        ];
        $entity = OrderAssembler::toCancelEntity($id, $payload, $operator);
        $order = $this->commandService->cancel($entity);
        return $this->success($order, '订单已取消');
    }

    #[PostMapping(path: 'export')]
    #[Permission(code: 'order:order:list')]
    public function export(OrderRequest $request): Result
    {
        return $this->success([
            'message' => '导出功能开发中，已接收请求。',
            'filters' => $request->validated(),
        ]);
    }
}
