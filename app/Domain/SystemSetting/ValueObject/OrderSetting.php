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
 * 订单配置值对象.
 */
final class OrderSetting
{
    public function __construct(
        private readonly int $autoCloseMinutes,
        private readonly int $autoConfirmDays,
        private readonly int $afterSaleDays,
        private readonly bool $enableInvoice,
        private readonly string $invoiceProvider,
        private readonly string $customerServicePhone,
    ) {}

    public function autoCloseMinutes(): int
    {
        return $this->autoCloseMinutes;
    }

    public function autoConfirmDays(): int
    {
        return $this->autoConfirmDays;
    }

    public function afterSaleDays(): int
    {
        return $this->afterSaleDays;
    }

    public function enableInvoice(): bool
    {
        return $this->enableInvoice;
    }

    public function invoiceProvider(): string
    {
        return $this->invoiceProvider;
    }

    public function customerServicePhone(): string
    {
        return $this->customerServicePhone;
    }
}
