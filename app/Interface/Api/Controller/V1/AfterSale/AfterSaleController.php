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

namespace App\Interface\Api\Controller\V1\AfterSale;

use App\Application\Api\AfterSale\AppApiAfterSaleCommandService;
use App\Application\Api\AfterSale\AppApiAfterSaleQueryService;
use App\Application\Api\Logistics\AppApiLogisticsTrackingQueryService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\AfterSale\AfterSaleApplyRequest;
use App\Interface\Api\Request\V1\AfterSale\AfterSaleReturnShipmentRequest;
use App\Interface\Api\Transformer\AfterSaleTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\HttpServer\Contract\RequestInterface;

#[Controller(prefix: '/api/v1/after-sales')]
#[Middleware(TokenMiddleware::class)]
final class AfterSaleController extends AbstractController
{
    public function __construct(
        private readonly AppApiAfterSaleQueryService $queryService,
        private readonly AppApiAfterSaleCommandService $commandService,
        private readonly AfterSaleTransformer $transformer,
        private readonly CurrentMember $currentMember,
        private readonly RequestInterface $request,
        private readonly AppApiLogisticsTrackingQueryService $logisticsTrackingQueryService,
    ) {}

    /**
     * 获取订单商品项的售后资格和当前售后信息.
     */
    #[GetMapping(path: 'eligibility')]
    public function eligibility(): Result
    {
        $orderId = (int) $this->request->query('order_id', 0);
        $orderItemId = (int) $this->request->query('order_item_id', 0);

        return $this->success($this->queryService->eligibility($this->currentMember->id(), $orderId, $orderItemId));
    }

    /**
     * 提交售后申请.
     */
    #[PostMapping(path: '')]
    public function apply(AfterSaleApplyRequest $request): Result
    {
        $afterSale = $this->commandService->apply($request->toDto($this->currentMember->id()));

        return $this->successWithTransform($afterSale, fn ($item) => $this->transformer->transform($item), '售后申请提交成功');
    }

    /**
     * 获取当前会员的售后单列表.
     */
    #[GetMapping(path: '')]
    public function index(): Result
    {
        $status = (string) $this->request->query('status', 'all');
        $page = (int) $this->request->query('page', 1);
        $pageSize = (int) $this->request->query('page_size', 10);
        $paginator = $this->queryService->paginateByMember($this->currentMember->id(), $status, $page, $pageSize);

        return $this->successWithPaginator($paginator, fn ($item) => $this->transformer->transform($item));
    }

    /**
     * 获取当前会员的售后单详情.
     */
    #[GetMapping(path: '{id}')]
    public function detail(int $id): Result
    {
        $afterSale = $this->queryService->detail($this->currentMember->id(), $id);

        return $this->successWithTransform($afterSale, fn ($item) => $this->transformer->transform($item));
    }

    #[GetMapping(path: '{id}/return-logistics')]
    public function returnLogistics(int $id): Result
    {
        return $this->success(
            $this->logisticsTrackingQueryService->trackAfterSaleReturn($this->currentMember->id(), $id)
        );
    }

    #[GetMapping(path: '{id}/reship-logistics')]
    public function reshipLogistics(int $id): Result
    {
        return $this->success(
            $this->logisticsTrackingQueryService->trackAfterSaleReship($this->currentMember->id(), $id)
        );
    }

    /**
     * 会员撤销待审核状态的售后申请.
     */
    #[PostMapping(path: '{id}/cancel')]
    public function cancel(int $id): Result
    {
        $this->commandService->cancel($this->currentMember->id(), $id);

        return $this->success([], '售后申请已撤销');
    }

    /**
     * 提交买家退货物流信息.
     */
    #[PostMapping(path: '{id}/return-shipment')]
    public function submitReturnShipment(int $id, AfterSaleReturnShipmentRequest $request): Result
    {
        $this->commandService->submitReturnShipment($request->toDto($id, $this->currentMember->id()));

        return $this->success([], '退货物流提交成功');
    }

    /**
     * 确认换货补发商品已收货.
     */
    #[PostMapping(path: '{id}/confirm-exchange-received')]
    public function confirmExchangeReceived(int $id): Result
    {
        $this->commandService->confirmExchangeReceived($this->currentMember->id(), $id);

        return $this->success([], '确认收货成功');
    }
}
