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

namespace App\Domain\Order\Trait;

use App\Domain\Order\Entity\OrderShipEntity;
use App\Domain\Order\ValueObject\OrderPackageValue;
use App\Domain\SystemSetting\ValueObject\OrderSetting;
use App\Domain\SystemSetting\ValueObject\ShippingSetting;
use Carbon\Carbon;

/**
 * 订单实体配置校验 Trait.
 */
trait OrderSettingsTrait
{
    public function applySubmissionPolicy(OrderSetting $orderSetting): void
    {
        $autoClose = $orderSetting->autoCloseMinutes();
        $this->setExpireTime(
            $autoClose > 0 ? Carbon::now()->addMinutes($autoClose) : null
        );
    }

    public function guardPreorderAllowed(bool $allowPreorder): void
    {
        if ($this->getOrderType() === 'preorder' && ! $allowPreorder) {
            throw new \DomainException('系统未开启预售功能，请勿提交预售订单。');
        }
    }

    public function ensureShippable(ShippingSetting $shippingSetting): void
    {
        $shipEntity = $this->getShipEntity();
        if (! $shipEntity instanceof OrderShipEntity) {
            throw new \DomainException('发货信息缺失，请重新提交。');
        }

        $packages = $shipEntity->getPackages();
        if ($packages === []) {
            throw new \DomainException('请至少提供一个包裹信息');
        }

        $allowed = $shippingSetting->supportedProviders();
        if ($allowed === []) {
            return;
        }

        foreach ($packages as $package) {
            \assert($package instanceof OrderPackageValue);
            $company = $package->getShippingCompany();
            if ($company === '') {
                throw new \DomainException('快递公司不能为空');
            }
            if (! $shippingSetting->isProviderSupported($company)) {
                throw new \DomainException(\sprintf('快递公司 %s 未在商城配置的支持列表中', $company));
            }
        }
    }
}
