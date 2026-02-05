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

namespace App\Application\Api\Payment;

use App\Domain\Member\Service\MemberService;
use App\Domain\Order\Service\OrderService;
use App\Domain\Payment\PayService;
use App\Domain\SystemSetting\Service\MallSettingService;
use Yansongda\Artful\Exception\ContainerException;

final class OrderPaymentApiService
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly MemberService $memberService,
        private readonly MallSettingService $mallSettingService,
        private readonly PayService $payService,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @throws ContainerException
     */
    public function payment(int $memberId, array $payload): array
    {
        // 获取订单数据
        $orderEntity = $this->orderService->getEntityById(0, $payload['order_no']);
        $memberEntity = $this->memberService->getInfoEntity($memberId);

        $systemPayment = $this->mallSettingService->payment();

        $orderEntity->setPayMethod($payload['pay_method']);

        $payService = $this->payService->init($orderEntity, $memberEntity);

        if ($orderEntity->getPayMethod() === 'balance') {
            return $payService->payByBalance();
        }
        return $payService->payByWechat($systemPayment->wechatConfig());
    }
}
