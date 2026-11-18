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

namespace App\Domain\Trade\Payment\ValueObject;

use App\Domain\Infrastructure\SystemSetting\ValueObject\PaymentSetting;
use App\Domain\Member\Entity\MemberEntity;
use App\Domain\Trade\Order\Entity\OrderEntity;
use App\Infrastructure\Error\OrderError;
use App\Infrastructure\Error\PaymentError;

final class PaymentPolicy
{
    public function assertOwner(OrderEntity $order, int $memberId): void
    {
        if ($order->getMemberId() !== $memberId) {
            throw OrderError::NOT_FOUND->exception();
        }
    }

    public function assertPayable(OrderEntity $order): void
    {
        if ($order->getStatus() !== 'pending' || $order->getPayStatus() !== 'pending') {
            throw OrderError::NOT_PAYABLE->exception();
        }
        if ($order->getExpireTime()?->isPast()) {
            throw OrderError::EXPIRED->exception();
        }
    }

    public function assertBalance(PaymentSetting $setting, int $amount, int $spentToday): void
    {
        if (! $setting->balanceEnabled()) {
            throw PaymentError::BALANCE_DISABLED->exception();
        }
        $config = $setting->balanceConfig();
        foreach (['min_amount', 'max_amount', 'daily_limit'] as $key) {
            $limit = (int) ($config[$key] ?? 0);
            if ($limit <= 0) {
                continue;
            }
            if (($key === 'min_amount' && $amount < $limit)
                || ($key === 'max_amount' && $amount > $limit)
                || ($key === 'daily_limit' && $spentToday + $amount > $limit)) {
                throw PaymentError::BALANCE_LIMIT_EXCEEDED->exception([$key]);
            }
        }
    }

    public function assertManualPayable(OrderEntity $order): void
    {
        $this->assertPayable($order);
        if ($order->getPayAmount() <= 0) {
            throw PaymentError::MANUAL_AMOUNT_INVALID->exception();
        }
    }

    public function wechatPayload(OrderEntity $order, MemberEntity $member): array
    {
        $this->assertOwner($order, $member->getId());
        $this->assertPayable($order);
        if (! $member->getOpenid() || $order->getPayAmount() <= 0) {
            throw PaymentError::WECHAT_REQUEST_INVALID->exception();
        }
        return [
            'out_trade_no' => $order->getOrderNo(),
            'description' => '订单 ' . $order->getOrderNo(),
            'amount' => ['total' => $order->getPayAmount(), 'currency' => 'CNY'],
            'payer' => ['openid' => $member->getOpenid()],
        ];
    }

    public function validateWechatSuccess(OrderEntity $order, array $payload, array $config): int
    {
        if (($payload['out_trade_no'] ?? '') !== $order->getOrderNo()
            || ($payload['mchid'] ?? '') !== (string) ($config['mch_id'] ?? $config['mchid'] ?? '')
            || ($payload['appid'] ?? '') !== (string) ($config['mini_app_id'] ?? $config['app_id'] ?? '')
            || empty($payload['transaction_id'])) {
            throw PaymentError::WECHAT_IDENTITY_MISMATCH->exception();
        }
        $amount = $payload['amount']['total'] ?? null;
        if ($amount !== $order->getPayAmount() || ($payload['amount']['currency'] ?? '') !== 'CNY') {
            throw PaymentError::WECHAT_AMOUNT_MISMATCH->exception();
        }
        return $amount;
    }
}
