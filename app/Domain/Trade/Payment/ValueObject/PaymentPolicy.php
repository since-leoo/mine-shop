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

final class PaymentPolicy
{
    public function assertOwner(OrderEntity $order, int $memberId): void
    {
        if ($order->getMemberId() !== $memberId) {
            throw new \DomainException('订单不存在');
        }
    }

    public function assertPayable(OrderEntity $order): void
    {
        if ($order->getStatus() !== 'pending' || $order->getPayStatus() !== 'pending') {
            throw new \DomainException('当前订单状态不可支付');
        }
        if ($order->getExpireTime()?->isPast()) {
            throw new \DomainException('订单已过期');
        }
    }

    public function assertBalance(PaymentSetting $setting, int $amount, int $spentToday): void
    {
        if (! $setting->balanceEnabled()) {
            throw new \DomainException('余额支付未启用');
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
                throw new \DomainException('余额支付超过配置限额: ' . $key);
            }
        }
    }

    public function assertManualPayable(OrderEntity $order): void
    {
        $this->assertPayable($order);
        if ($order->getPayAmount() <= 0) {
            throw new \DomainException('人工认款金额必须大于零');
        }
    }

    public function wechatPayload(OrderEntity $order, MemberEntity $member): array
    {
        $this->assertOwner($order, $member->getId());
        $this->assertPayable($order);
        if (! $member->getOpenid() || $order->getPayAmount() <= 0) {
            throw new \DomainException('微信支付需要有效的会员 openid 和正数金额');
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
            throw new \DomainException('微信支付通知身份不匹配');
        }
        $amount = $payload['amount']['total'] ?? null;
        if ($amount !== $order->getPayAmount() || ($payload['amount']['currency'] ?? '') !== 'CNY') {
            throw new \DomainException('微信支付通知金额或币种不匹配');
        }
        return $amount;
    }
}
