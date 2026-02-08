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

use App\Domain\Infrastructure\SystemSetting\Service\DomainMallSettingService;
use App\Domain\Member\Api\Command\DomainApiMemberCommandService;
use App\Domain\Trade\Order\Api\Command\DomainApiOrderCommandService;
use App\Domain\Trade\Payment\DomainPayService;
use Yansongda\Artful\Exception\ContainerException;

final class AppApiOrderPaymentService
{
    public function __construct(
        private readonly DomainApiOrderCommandService $orderCommandService,
        private readonly DomainApiMemberCommandService $memberCommandService,
        private readonly DomainMallSettingService $mallSettingService,
        private readonly DomainPayService $payService,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @throws ContainerException
     */
    public function payment(int $memberId, array $payload): array
    {
        $orderEntity = $this->orderCommandService->getEntity(0, $payload['order_no']);
        $memberEntity = $this->memberCommandService->getEntity($memberId);

        $systemPayment = $this->mallSettingService->payment();

        $orderEntity->setPayMethod($payload['pay_method']);

        $payService = $this->payService->init($orderEntity, $memberEntity);

        if ($orderEntity->getPayMethod() === 'balance') {
            return $payService->payByBalance();
        }
        return $payService->payByWechat($systemPayment->wechatConfig());
    }
}
