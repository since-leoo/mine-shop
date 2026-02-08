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

namespace App\Interface\Api\Controller\V1;

use App\Application\Api\Order\AppApiOrderCommandService;
use App\Application\Api\Order\AppApiOrderQueryService;
use App\Application\Api\Payment\AppApiOrderPaymentService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\OrderCancelRequest;
use App\Interface\Api\Request\V1\OrderCommitRequest;
use App\Interface\Api\Request\V1\OrderConfirmReceiptRequest;
use App\Interface\Api\Request\V1\OrderListRequest;
use App\Interface\Api\Request\V1\OrderPaymentRequest;
use App\Interface\Api\Request\V1\OrderPreviewRequest;
use App\Interface\Api\Transformer\OrderCheckoutTransformer;
use App\Interface\Api\Transformer\OrderTransformer;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

#[Controller(prefix: '/api/v1/order')]
#[Middleware(TokenMiddleware::class)]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly AppApiOrderCommandService $checkoutService,
        private readonly CurrentMember $currentMember,
        private readonly AppApiOrderPaymentService $paymentApiService,
        private readonly AppApiOrderQueryService $orderQueryService,
        private readonly OrderTransformer $orderTransformer,
        private readonly OrderCheckoutTransformer $checkoutTransformer
    ) {}

    #[PostMapping(path: 'preview')]
    #[RateLimit(create: 60, capacity: 20)]
    public function preview(OrderPreviewRequest $request): Result
    {
        $orderEntity = $this->checkoutService->preview($request->toDto($this->currentMember->id()));
        return $this->success($this->checkoutTransformer->transform($orderEntity), '订单预览');
    }

    #[PostMapping(path: 'submit')]
    #[RateLimit(create: 30, capacity: 10)]
    public function submit(OrderCommitRequest $request): Result
    {
        $result = $this->checkoutService->submit($request->toDto($this->currentMember->id()));

        return $this->success($result, '订单提交中');
    }

    /**
     * 轮询异步下单结果.
     */
    #[GetMapping(path: 'submit-result/{tradeNo}')]
    public function submitResult(string $tradeNo): Result
    {
        $result = $this->checkoutService->getSubmitResult($tradeNo);

        return $this->success($result);
    }

    #[PostMapping(path: 'payment')]
    public function payment(OrderPaymentRequest $request): Result
    {
        return $this->success($this->paymentApiService->payment($this->currentMember->id(), $request->validated()));
    }

    /**
     * 获取订单列表.
     */
    #[GetMapping(path: 'list')]
    public function list(OrderListRequest $request): Result
    {
        $validated = $request->validated();
        $status = $validated['status'] ?? 'all';
        $page = (int) ($validated['page'] ?? 1);
        $pageSize = (int) ($validated['page_size'] ?? 10);

        $orders = $this->orderQueryService->getMemberOrderList(
            $this->currentMember->id(),
            $status,
            $page,
            $pageSize
        );

        return $this->successWithPaginator($orders, fn ($order) => $this->orderTransformer->transform($order));
    }

    /**
     * 获取订单详情.
     */
    #[GetMapping(path: 'detail/{orderNo}')]
    public function detail(string $orderNo): Result
    {
        $order = $this->orderQueryService->getOrderDetail($this->currentMember->id(), $orderNo);

        if (! $order) {
            return $this->fail('订单不存在');
        }

        return $this->successWithTransform($order, fn ($order) => $this->orderTransformer->transformDetail($order));
    }

    /**
     * 获取订单统计.
     */
    #[GetMapping(path: 'statistics')]
    public function statistics(): Result
    {
        $statistics = $this->orderQueryService->getOrderStatistics($this->currentMember->id());

        return $this->success($statistics);
    }

    /**
     * 获取待支付订单的支付信息（用于重新支付场景）.
     */
    #[GetMapping(path: 'pay-info/{orderNo}')]
    public function payInfo(string $orderNo): Result
    {
        $order = $this->orderQueryService->getOrderDetail($this->currentMember->id(), $orderNo);

        if (! $order) {
            return $this->fail('订单不存在');
        }

        if ($order->status !== 'pending') {
            return $this->fail('订单状态不支持支付');
        }

        return $this->success([
            'trade_no' => $order->order_no,
            'pay_amount' => (int) $order->pay_amount,
            'total_amount' => (int) $order->total_amount,
            'pay_methods' => $this->checkoutService->resolvePaymentMethods(),
        ]);
    }

    /**
     * 取消订单（仅待付款状态）.
     */
    #[PostMapping(path: 'cancel')]
    public function cancel(OrderCancelRequest $request): Result
    {
        $this->checkoutService->cancel(
            $this->currentMember->id(),
            $request->validated()['order_no']
        );

        return $this->success([], '订单已取消');
    }

    /**
     * 确认收货（仅已发货状态）.
     */
    #[PostMapping(path: 'confirm-receipt')]
    public function confirmReceipt(OrderConfirmReceiptRequest $request): Result
    {
        $this->checkoutService->confirmReceipt(
            $this->currentMember->id(),
            $request->validated()['order_no']
        );

        return $this->success([], '已确认收货');
    }
}
