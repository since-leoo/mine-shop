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

namespace App\Interface\Api\Controller\V1\Payment;

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Application\Api\Payment\AppPaymentCallbackService;
use App\Domain\Trade\Payment\Contract\PaymentGatewayInterface;
use App\Infrastructure\Service\Pay\WechatPayConfigResolver;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\PostMapping;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

#[Controller(prefix: '/api/v1/payment/wechat')]
final class PaymentNotifyController
{
    public function __construct(
        private readonly AppPaymentCallbackService $callbacks,
        private readonly PaymentGatewayInterface $payService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly WechatPayConfigResolver $wechatPayConfigResolver,
        private readonly ServerRequestInterface $request,
    ) {}

    #[PostMapping(path: 'pay-notify')]
    public function payNotify(): ResponseInterface
    {
        $config = $this->getWechatConfig();
        $payload = $this->payService->callback($this->request, $config);
        $this->callbacks->paid($payload);

        return $this->payService->success($config);
    }

    #[PostMapping(path: 'refund-notify')]
    public function refundNotify(): ResponseInterface
    {
        $config = $this->getWechatConfig();
        $payload = $this->payService->callback($this->request, $config);
        $this->callbacks->refunded($payload);

        return $this->payService->success($config);
    }

    private function getWechatConfig(): array
    {
        return $this->wechatPayConfigResolver->resolve($this->mallSettingService->payment()->wechatConfig());
    }
}
