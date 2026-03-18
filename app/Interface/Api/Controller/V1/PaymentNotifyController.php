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

use App\Domain\Trade\AfterSale\Service\DomainAfterSaleRefundCallbackService;
use App\Domain\Trade\Order\Service\DomainOrderService;
use App\Domain\Trade\Payment\DomainPayService;
use App\Infrastructure\Service\Pay\YsdPayService;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Controller(prefix: '/api/v1/payment/wechat')]
final class PaymentNotifyController
{
    public function __construct(
        private readonly DomainAfterSaleRefundCallbackService $refundCallbackService,
        private readonly DomainOrderService $orderService,
        private readonly DomainPayService $domainPayService,
        private readonly YsdPayService $payService,
        private readonly ServerRequestInterface $request,
    ) {}

    #[PostMapping(path: 'pay-notify')]
    public function payNotify(): ResponseInterface
    {
        $config = $this->getWechatConfig();
        $payload = $this->payService->callback($this->request, $config);
        $orderNo = (string) ($payload['out_trade_no'] ?? '');

        if ($orderNo !== '') {
            $orderEntity = $this->orderService->getEntity(0, $orderNo);
            $this->domainPayService->notify($orderEntity, $payload);
        }

        return $this->payService->success($config);
    }

    #[PostMapping(path: 'refund-notify')]
    public function refundNotify(): ResponseInterface
    {
        $config = $this->getWechatConfig();
        $payload = $this->payService->callback($this->request, $config);
        $this->refundCallbackService->handleWechatRefundCallback($payload);

        return $this->payService->success($config);
    }

    private function getWechatConfig(): array
    {
        if (! function_exists('config')) {
            return [];
        }

        try {
            $config = config('pay.default.wechat', config('pay.wechat.default', []));
            return is_array($config) ? $config : [];
        } catch (\Throwable) {
            return [];
        }
    }
}
