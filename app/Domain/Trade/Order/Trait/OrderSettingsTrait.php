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

namespace App\Domain\Trade\Order\Trait;

use App\Domain\Infrastructure\SystemSetting\ValueObject\OrderSetting;
use App\Domain\Infrastructure\SystemSetting\ValueObject\ShippingSetting;
use App\Domain\Trade\Order\Entity\OrderShipEntity;
use App\Domain\Trade\Order\ValueObject\OrderPackageValue;
use App\Infrastructure\Error\OrderError;
use App\Infrastructure\Error\ShippingError;
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

    public function ensureShippable(ShippingSetting $shippingSetting): void
    {
        $shipEntity = $this->getShipEntity();
        if (! $shipEntity instanceof OrderShipEntity) {
            throw ShippingError::SHIPMENT_INFO_MISSING->exception();
        }

        $packages = $shipEntity->getPackages();
        if ($packages === []) {
            throw OrderError::PACKAGE_REQUIRED->exception();
        }

        $allowed = $shippingSetting->supportedProviders();
        if ($allowed === []) {
            return;
        }

        foreach ($packages as $package) {
            \assert($package instanceof OrderPackageValue);
            $company = $package->getShippingCompany();
            if ($company === '') {
                throw ShippingError::CARRIER_REQUIRED->exception();
            }
            if (! $shippingSetting->isProviderSupported($company)) {
                throw ShippingError::CARRIER_UNSUPPORTED->exception([$company]);
            }
        }
    }
}
