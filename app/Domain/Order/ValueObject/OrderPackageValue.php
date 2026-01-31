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

namespace App\Domain\Order\ValueObject;

use App\Domain\Order\Enum\ShippingStatus;
use Carbon\Carbon;

final class OrderPackageValue
{
    private string $packageNo = '';

    private string $shippingCompany = '';

    private string $shippingNo = '';

    private int $quantity = 0;

    private float $weight = 0.0;

    private string $status = ShippingStatus::PENDING->value;

    private string $remark = '';

    private ?Carbon $shippedAt = null;

    private ?Carbon $deliveredAt = null;

    public function setPackageNo(string $packageNo): void
    {
        $this->packageNo = $packageNo;
    }

    public function getPackageNo(): string
    {
        return $this->packageNo;
    }

    public function setShippingCompany(string $shippingCompany): void
    {
        $this->shippingCompany = $shippingCompany;
    }

    public function getShippingCompany(): string
    {
        return $this->shippingCompany;
    }

    public function setShippingNo(string $shippingNo): void
    {
        $this->shippingNo = $shippingNo;
    }

    public function getShippingNo(): string
    {
        return $this->shippingNo;
    }

    public function setQuantity(int $quantity): void
    {
        $this->quantity = max(0, $quantity);
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = max(0, $weight);
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setRemark(string $remark): void
    {
        $this->remark = $remark;
    }

    public function getRemark(): string
    {
        return $this->remark;
    }

    public function setShippedAt(?Carbon $shippedAt): void
    {
        $this->shippedAt = $shippedAt;
    }

    public function getShippedAt(): ?Carbon
    {
        return $this->shippedAt ?? Carbon::now();
    }

    public function setDeliveredAt(?Carbon $deliveredAt): void
    {
        $this->deliveredAt = $deliveredAt;
    }

    public function getDeliveredAt(): ?Carbon
    {
        return $this->deliveredAt;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(int $orderId): array
    {
        return [
            'package_no' => $this->getPackageNo(),
            'express_company' => $this->getShippingCompany(),
            'express_no' => $this->getShippingNo(),
            'status' => $this->getStatus(),
            'weight' => $this->getWeight(),
            'remark' => $this->getRemark(),
            'shipped_at' => $this->getShippedAt()?->toDateTimeString(),
            'delivered_at' => $this->getDeliveredAt()?->toDateTimeString(),
        ];
    }
}
