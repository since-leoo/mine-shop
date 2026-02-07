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

use App\Application\Api\Order\OrderCommandApiService;
use App\Application\Api\Payment\OrderPaymentApiService;
use App\Interface\Api\Middleware\TokenMiddleware;
use App\Interface\Api\Request\V1\OrderCommitRequest;
use App\Interface\Api\Request\V1\OrderPaymentRequest;
use App\Interface\Api\Request\V1\OrderPreviewRequest;
use App\Interface\Common\Controller\AbstractController;
use App\Interface\Common\CurrentMember;
use App\Interface\Common\Result;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use Hyperf\RateLimit\Annotation\RateLimit;

#[Controller(prefix: '/api/v1/order')]
#[Middleware(TokenMiddleware::class)]
final class OrderController extends AbstractController
{
    public function __construct(
        private readonly OrderCommandApiService $checkoutService,
        private readonly CurrentMember $currentMember,
        private readonly OrderPaymentApiService $paymentApiService
    ) {}

    #[PostMapping(path: 'preview')]
    #[RateLimit(create: 60, capacity: 20)]
    public function preview(OrderPreviewRequest $request): Result
    {
        $data = $this->checkoutService->preview($request->toDto($this->currentMember->id()));
        return $this->success($data, '订单预览');
    }

    #[PostMapping(path: 'submit')]
    #[RateLimit(create: 30, capacity: 10)]
    public function submit(OrderCommitRequest $request): Result
    {
        $data = $this->checkoutService->submit($request->toDto($this->currentMember->id()));
        return $this->success($data, '下单成功');
    }

    #[PostMapping(path: 'payment')]
    public function payment(OrderPaymentRequest $request): Result
    {
        return $this->success($this->paymentApiService->payment($this->currentMember->id(), $request->validated()));
    }
}
