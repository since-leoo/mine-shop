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
use App\Infrastructure\Exception\System\BusinessException;
use App\Interface\Common\ResultCode;
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
            $this->assertBalancePaymentConfig($orderEntity->getPayAmount(), $systemPayment->balanceConfig());
            return $payService->payByBalance();
        }
        return $payService->payByWechat($systemPayment->wechatConfig());
    }

    /**
     * @param array<string, mixed> $config
     */
    private function assertBalancePaymentConfig(int $payAmount, array $config): void
    {
        $minAmount = $this->positiveAmount($config['min_amount'] ?? null);
        if ($minAmount !== null && $payAmount < $minAmount) {
            throw new BusinessException(ResultCode::FAIL, \sprintf('余额支付金额不能低于 %s 元', $this->formatCentAmount($minAmount)));
        }

        $maxAmount = $this->positiveAmount($config['max_amount'] ?? null);
        if ($maxAmount !== null && $payAmount > $maxAmount) {
            throw new BusinessException(ResultCode::FAIL, \sprintf('余额支付金额不能高于 %s 元', $this->formatCentAmount($maxAmount)));
        }

        $dailyLimit = $this->positiveAmount($config['daily_limit'] ?? null);
        if ($dailyLimit !== null && $payAmount > $dailyLimit) {
            throw new BusinessException(ResultCode::FAIL, \sprintf('余额支付金额不能超过每日限额 %s 元', $this->formatCentAmount($dailyLimit)));
        }
    }

    private function positiveAmount(mixed $amount): ?int
    {
        if (! \is_numeric($amount)) {
            return null;
        }

        $amount = (int) $amount;
        return $amount > 0 ? $amount : null;
    }

    private function formatCentAmount(int $amount): string
    {
        return number_format($amount / 100, 2, '.', '');
    }
}
