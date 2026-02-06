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

namespace App\Domain\Order\Entity;

use App\Domain\Order\Enum\ShippingStatus;
use App\Domain\Order\ValueObject\OrderPackageValue;
use Carbon\Carbon;

final class OrderShipEntity
{
    private int $orderId = 0;

    /**
     * @var OrderPackageValue[]
     */
    private array $packages = [];

    private ?Carbon $shippedAt = null;

    public function setOrderId(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): int
    {
        return $this->orderId;
    }

    /**
     * @param array<int, array<string, mixed>> $packages
     */
    public function setPackages(array $packages): void
    {
        $values = [];

        foreach ($packages as $package) {
            $company = (string) ($package['shipping_company'] ?? '');
            $number = (string) ($package['shipping_no'] ?? '');
            if ($company === '' || $number === '') {
                continue;
            }

            $value = new OrderPackageValue();
            $value->setShippingCompany($company);
            $value->setShippingNo($number);
            $value->setRemark((string) ($package['remark'] ?? ''));
            $value->setQuantity((int) ($package['quantity'] ?? 0));
            $value->setWeight((float) ($package['weight'] ?? 0));
            $value->setStatus(ShippingStatus::SHIPPED->value);
            $value->setShippedAt($this->getShippedAt());
            $values[] = $value;
        }

        $this->packages = $values;
    }

    /**
     * @return OrderPackageValue[]
     */
    public function getPackages(): array
    {
        return $this->packages;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getPackagePayloads(): array
    {
        return array_map(function (OrderPackageValue $package) {
            return $package->toArray($this->orderId);
        }, $this->packages);
    }

    public function setShippedAt(?Carbon $shippedAt): void
    {
        $this->shippedAt = $shippedAt;
    }

    public function getShippedAt(): Carbon
    {
        return $this->shippedAt ?? Carbon::now();
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->orderId,
            'packages' => $this->getPackagePayloads(),
            'shipped_at' => $this->shippedAt?->toDateTimeString(),
        ];
    }
}
