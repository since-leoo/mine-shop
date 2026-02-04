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

namespace App\Domain\SystemSetting\ValueObject;

/**
 * 支付配置值对象.
 */
final class PaymentSetting
{
    /**
     * @param array<string, mixed> $wechatConfig
     * @param array<string, mixed> $balanceConfig
     */
    public function __construct(
        private readonly bool $wechatEnabled,
        private readonly array $wechatConfig,
        private readonly bool $refundReview,
        private readonly int $settlementCycleDays,
        private readonly bool $balanceEnabled,
        private readonly array $balanceConfig,
    ) {}

    public function wechatEnabled(): bool
    {
        return $this->wechatEnabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function wechatConfig(): array
    {
        return $this->wechatConfig;
    }

    public function refundReview(): bool
    {
        return $this->refundReview;
    }

    public function settlementCycleDays(): int
    {
        return $this->settlementCycleDays;
    }

    public function balanceEnabled(): bool
    {
        return $this->balanceEnabled;
    }

    /**
     * @return array<string, mixed>
     */
    public function balanceConfig(): array
    {
        return $this->balanceConfig;
    }
}
